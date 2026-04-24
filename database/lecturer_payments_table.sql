CREATE TABLE IF NOT EXISTS `lecturer_payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `lecturer_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `payment_month` varchar(7) NOT NULL,
  `payment_date` datetime DEFAULT NULL,
  `status` enum('paid','pending') NOT NULL DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `lecturer_id` (`lecturer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
