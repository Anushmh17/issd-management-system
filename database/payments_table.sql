CREATE TABLE IF NOT EXISTS `student_payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `month` varchar(7) NOT NULL,
  `monthly_fee` decimal(10,2) NOT NULL DEFAULT 0.00,
  `previous_balance` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_due` decimal(10,2) NOT NULL DEFAULT 0.00,
  `amount_paid` decimal(10,2) NOT NULL DEFAULT 0.00,
  `balance` decimal(10,2) NOT NULL DEFAULT 0.00,
  `status` enum('paid','partial','pending','overdue') NOT NULL DEFAULT 'pending',
  `payment_date` datetime NOT NULL,
  `next_due_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `student_id` (`student_id`),
  KEY `course_id` (`course_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
