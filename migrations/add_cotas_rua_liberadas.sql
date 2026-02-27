-- Adiciona coluna para armazenar os números liberados do range de cotas de rua
-- Armazena um JSON array, ex: [3, 7, 42]
-- Quando NULL ou [], todos os números do range estão bloqueados

ALTER TABLE `product_list` 
ADD COLUMN `cotas_rua_liberadas` TEXT NULL DEFAULT NULL 
AFTER `cotas_rua_fim`;
