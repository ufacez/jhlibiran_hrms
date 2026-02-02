-- ============================================
-- BIR TAX BRACKETS TABLE
-- Philippine Withholding Tax (TRAIN Law)
-- ============================================

DROP TABLE IF EXISTS `bir_tax_brackets`;
CREATE TABLE `bir_tax_brackets` (
    `bracket_id` INT(11) NOT NULL AUTO_INCREMENT,
    `bracket_level` INT(11) NOT NULL,
    `lower_bound` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `upper_bound` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `base_tax` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `tax_rate` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    `is_exempt` TINYINT(1) NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`bracket_id`),
    UNIQUE KEY `unique_bracket_level` (`bracket_level`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- INSERT 2023 TRAIN LAW TAX BRACKETS (Monthly)
-- Based on BIR Revenue Regulations
-- ============================================
INSERT INTO `bir_tax_brackets` (`bracket_level`, `lower_bound`, `upper_bound`, `base_tax`, `tax_rate`, `is_exempt`) VALUES
(1, 0.00, 20833.00, 0.00, 0.00, 1),
(2, 20833.00, 33332.00, 0.00, 15.00, 0),
(3, 33333.00, 66666.00, 1875.00, 20.00, 0),
(4, 66667.00, 166666.00, 8541.80, 25.00, 0),
(5, 166667.00, 666666.00, 33541.80, 30.00, 0),
(6, 666667.00, 99999999.00, 183541.80, 35.00, 0);

-- ============================================
-- Note: Tax Computation Formula
-- ============================================
-- For monthly income falling in a bracket:
-- Withholding Tax = Base Tax + ((Monthly Income - Lower Bound) × Tax Rate%)
-- 
-- Example: Monthly Income = ₱50,000
-- Falls in Bracket 3 (₱33,333 - ₱66,666)
-- Tax = ₱1,875 + ((₱50,000 - ₱33,333) × 20%)
-- Tax = ₱1,875 + (₱16,667 × 0.20)
-- Tax = ₱1,875 + ₱3,333.40
-- Tax = ₱5,208.40
-- ============================================
