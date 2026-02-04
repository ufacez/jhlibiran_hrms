-- Create table for worker employment history
CREATE TABLE IF NOT EXISTS `worker_employment_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `worker_id` int(11) NOT NULL,
  `from_date` DATE DEFAULT NULL,
  `to_date` DATE DEFAULT NULL,
  `company` VARCHAR(255) DEFAULT NULL,
  `position` VARCHAR(255) DEFAULT NULL,
  `salary_per_day` DECIMAL(10,2) DEFAULT NULL,
  `reason_for_leaving` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX (`worker_id`),
  CONSTRAINT `fk_weh_worker` FOREIGN KEY (`worker_id`) REFERENCES `workers`(`worker_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
