-- Script pour attribuer les services admin à Emma Roberts
-- Ce script attribue tous les services sans branch_id spécifique au salon de Emma Roberts

-- Trouver l'ID d'Emma Roberts
SET @emma_id = (SELECT id FROM users WHERE email = 'emma.roberts@glamourcuts.co.uk' LIMIT 1);

-- Trouver le branch_id de Glamour Cuts (premier salon)
SET @glamour_branch_id = (SELECT id FROM branches WHERE name = 'Glamour Cuts' LIMIT 1);

-- Assigner Emma Roberts comme manager de Glamour Cuts si ce n'est pas déjà fait
UPDATE branches SET manager_id = @emma_id WHERE id = @glamour_branch_id AND manager_id IS NULL;

-- Attribuer tous les services sans association de branch à Glamour Cuts
INSERT INTO service_branches (service_id, branch_id, service_price, duration_min)
SELECT s.id, @glamour_branch_id, s.default_price, s.duration_min
FROM services s
WHERE s.id NOT IN (SELECT service_id FROM service_branches WHERE branch_id = @glamour_branch_id)
ON DUPLICATE KEY UPDATE branch_id = branch_id;

-- Afficher les résultats
SELECT 'Emma Roberts ID:' as info, @emma_id as value
UNION ALL
SELECT 'Glamour Cuts Branch ID:', @glamour_branch_id
UNION ALL
SELECT 'Services attribués:', COUNT(*) 
FROM service_branches WHERE branch_id = @glamour_branch_id;
