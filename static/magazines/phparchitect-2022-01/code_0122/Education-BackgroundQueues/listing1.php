$pdo = new \\PDO(...);
while (true) {
    foreach ($pdo->query('SELECT * FROM report_queue LIMIT 1') as $row) {
        // Read the message and do the work
        // Remove the message from the queue
        $pdo
            ->prepare('DELETE FROM report_queue WHERE id = :id')
            ->execute(['id' => $row['id'])
        ;
    }
    sleep(30);
}
