ALTER TABLE `product_list`
ADD COLUMN `cotas_rua_ranges` TEXT NULL DEFAULT NULL
AFTER `cotas_rua_liberadas`;
