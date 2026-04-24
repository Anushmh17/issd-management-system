CREATE TABLE IF NOT EXISTS `leads` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `phone` varchar(50) NOT NULL,
  `source` enum('Facebook','WhatsApp','Walk-in','Other') NOT NULL DEFAULT 'Other',
  `status` enum('new','talking','converted','not_interested') NOT NULL DEFAULT 'new',
  `next_followup_datetime` datetime DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
