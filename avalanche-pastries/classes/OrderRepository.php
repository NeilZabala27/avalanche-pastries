<?php
class OrderRepository extends Repository
{
    // The SELECT shared by the list and the detail page.
    private function selectSql(bool $withDesign): string
    {
        return "SELECT o.order_id, o.customer_id, o.preorder_id, o.order_date, o.delivery_date,
                       o.delivery_time, o.order_status, o.shipping_fee, o.is_customized, o.terms_given,
                       c.customer_name, c.address, c.contact_number,
                       pre.preorder_date,
                       p.total_amount, p.payment_status, p.payment_method,
                       p.down_payment, p.down_payment_status"
             . ($withDesign ? ", cd.design_description" : "")
             . " FROM customer_order o
                 JOIN customer c ON c.customer_id = o.customer_id
                 LEFT JOIN preorder pre ON pre.preorder_id = o.preorder_id
                 LEFT JOIN payment p ON p.order_id = o.order_id"
             . ($withDesign ? " LEFT JOIN cake_design cd ON cd.order_id = o.order_id" : "");
    }

    public function findAll(): array
    {
        return $this->search();
    }

    // Orders for customers whose name contains $search, optionally for one month (YYYY-MM).
    // $dateBy says which date the month is matched against: 'order' (default) or 'delivery'.
    // $preorder narrows the list: '' = all orders, 'none' = orders without a pre-order,
    // or a pre-order id = only the orders that came from that pre-order.
    public function search(string $search = '', string $month = '', string $dateBy = 'order', string $preorder = ''): array
    {
        $sql = $this->selectSql(false) . " WHERE c.customer_name LIKE ?";
        $params = ['%' . $search . '%'];

        if ($preorder === 'none') {
            $sql .= " AND o.preorder_id IS NULL";
        } elseif ($preorder !== '' && ctype_digit($preorder)) {
            $sql .= " AND o.preorder_id = ?";
            $params[] = (int)$preorder;
        }

        // The chosen date is used for the month filter AND for the sort order (newest first).
        $column = $dateBy === 'delivery' ? 'o.delivery_date' : 'o.order_date';   // fixed list, never user text
        $start = self::monthStart($month);
        if ($start !== null) {
            $sql .= " AND $column >= ? AND $column < ?";
            $params[] = $start;
            $params[] = date('Y-m-d', strtotime($start . ' +1 month'));
        }
        $sql .= " ORDER BY $column DESC, o.order_id DESC";

        $statement = $this->pdo->prepare($sql);
        $statement->execute($params);
        return array_map(fn($row) => Order::fromRow($row), $statement->fetchAll(PDO::FETCH_ASSOC));
    }

    // "2026-10" (or a typed month such as "October 2026") -> "2026-10-01". null when empty or not a month.
    private static function monthStart(string $month): ?string
    {
        $month = trim($month);
        if ($month === '') {
            return null;
        }
        if (preg_match('/^(\d{4})-(0[1-9]|1[0-2])$/', $month, $m)) {
            return $m[1] . '-' . $m[2] . '-01';
        }
        $time = strtotime('1 ' . $month);
        return $time === false ? null : date('Y-m-01', $time);
    }

    // One complete order with its items, payment and design.
    public function findById(int $id): ?Order
    {
        $statement = $this->pdo->prepare($this->selectSql(true) . " WHERE o.order_id = ? LIMIT 1");
        $statement->execute([$id]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }

        $statement = $this->pdo->prepare(
            "SELECT oi.product_id, pr.product_name, oi.quantity
             FROM order_item oi
             JOIN product pr ON pr.product_id = oi.product_id
             WHERE oi.order_id = ?
             ORDER BY pr.product_name");
        $statement->execute([$id]);
        return Order::fromRow($row, $statement->fetchAll(PDO::FETCH_ASSOC));
    }

    // The pre-order batches for the dropdown: [['preorder_id' => .., 'preorder_date' => ..], ...]
    public function findPreorders(): array
    {
        return $this->pdo->query("SELECT preorder_id, preorder_date FROM preorder ORDER BY preorder_date DESC")
                         ->fetchAll(PDO::FETCH_ASSOC);
    }

    // Saves the order, its items, payment, cake design and (if new) its customer.
    // All-or-nothing: if any part fails, nothing is saved.
    public function create(Order $order): int
    {
        $this->requireCustomerAndItems($order);

        $this->pdo->beginTransaction();
        try {
            $this->saveCustomerIfNew($order->getCustomer());

            $this->pdo->prepare(
                "INSERT INTO customer_order
                    (customer_id, preorder_id, order_date, delivery_date, delivery_time,
                     order_status, shipping_fee, is_customized, terms_given)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)")
                ->execute($this->orderValues($order));
            $orderId = (int)$this->pdo->lastInsertId();

            $this->insertChildren($orderId, $order);

            $this->pdo->commit();
            return $orderId;
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    // Changes an existing order: its own columns, and its items, design and payment are rewritten.
    // All-or-nothing, like create().
    public function update(int $id, Order $order): void
    {
        $this->requireCustomerAndItems($order);

        $this->pdo->beginTransaction();
        try {
            $this->saveCustomerIfNew($order->getCustomer());

            $statement = $this->pdo->prepare(
                "UPDATE customer_order
                 SET customer_id = ?, preorder_id = ?, order_date = ?, delivery_date = ?, delivery_time = ?,
                     order_status = ?, shipping_fee = ?, is_customized = ?, terms_given = ?
                 WHERE order_id = ?");
            $statement->execute(array_merge($this->orderValues($order), [$id]));

            $this->deleteChildren($id);
            $this->insertChildren($id, $order);

            $this->pdo->commit();
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    // Removes an order together with its items, payment, cake design and receipts.
    public function delete(int $id): void
    {
        $this->pdo->beginTransaction();
        try {
            $this->deleteChildren($id);
            $statement = $this->pdo->prepare("DELETE FROM customer_order WHERE order_id = ?");
            $statement->execute([$id]);
            if ($statement->rowCount() === 0) {
                throw new DomainException('That order no longer exists.');
            }
            $this->pdo->commit();
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    // ---- shared by create / update / delete ----

    private function requireCustomerAndItems(Order $order): void
    {
        if ($order->getCustomer() === null) {
            throw new LogicException('An order needs a customer.');
        }
        if (!$order->getItems()) {
            throw new LogicException('An order needs at least one product.');
        }
    }

    private function saveCustomerIfNew(Customer $customer): void
    {
        if ($customer->getId() === null) {
            (new CustomerRepository($this->pdo))->save($customer);
        }
    }

    // The values of the customer_order columns, in the order the INSERT / UPDATE lists them.
    private function orderValues(Order $order): array
    {
        return [
            $order->getCustomer()->getId(), $order->getPreorderId(), $order->getOrderDate(),
            $order->getDeliveryDate(), $order->getDeliveryTime(), $order->getStatus(),
            $order->getShippingFee(), $order->isCustomized() ? 1 : 0, $order->isTermsGiven() ? 1 : 0,
        ];
    }

    private function insertChildren(int $orderId, Order $order): void
    {
        if ($order instanceof CustomizedCakeOrder) {
            $this->pdo->prepare("INSERT INTO cake_design (order_id, design_description) VALUES (?, ?)")
                      ->execute([$orderId, $order->getDesignDescription()]);
        }

        $statement = $this->pdo->prepare("INSERT INTO order_item (order_id, product_id, quantity) VALUES (?, ?, ?)");
        foreach ($order->getItems() as $item) {
            $statement->execute([$orderId, $item->getProduct()->getId(), $item->getQuantity()]);
        }

        $payment = $order->getPayment();
        if ($payment !== null) {
            $this->pdo->prepare(
                "INSERT INTO payment (order_id, total_amount, payment_status, payment_method,
                                      down_payment, down_payment_status)
                 VALUES (?, ?, ?, ?, ?, ?)")
                ->execute([$orderId, $payment->getTotal(), $payment->getStatus(), $payment->getMethod(),
                           $payment->getDownPayment(), $payment->getDownPaymentStatus()]);
        }
    }

    private function deleteChildren(int $orderId): void
    {
        foreach (['receipt', 'payment', 'order_item', 'cake_design'] as $table) {
            $this->pdo->prepare("DELETE FROM $table WHERE order_id = ?")->execute([$orderId]);
        }
    }

    public function updateStatus(int $id, string $status): void
    {
        if (!in_array($status, Order::STATUSES, true)) {
            throw new InvalidArgumentException('Choose a valid order status.');
        }
        $this->pdo->prepare("UPDATE customer_order SET order_status = ? WHERE order_id = ?")
                  ->execute([$status, $id]);
    }
}
