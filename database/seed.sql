-- ============================================================
-- NEXUS — SEED DE DÉMONSTRATION
-- Données de test pour les 2 comptes de démo.
--
-- IMPORTANT : Ne pas utiliser en production.
-- Supprimer ces comptes ou changer les mots de passe avant
-- toute mise en ligne réelle.
--
-- Mot de passe des deux comptes : demo1234
-- ============================================================

USE nexus;

-- --------------------------------------------------------
-- Utilisateurs de démo
-- --------------------------------------------------------
INSERT INTO users (id, name, email, password_hash, currency, theme, locale) VALUES
(1, 'Alex Démo', 'alex@demo.nexus', '$2b$12$hi4PfmdNLA2o/ZgWm5lS9eCM5DKbqEUVdLDS9hUt5PMldZQg0l/U6', 'EUR', 'dark', 'fr'),
(2, 'Sam Démo',  'sam@demo.nexus',  '$2b$12$hi4PfmdNLA2o/ZgWm5lS9eCM5DKbqEUVdLDS9hUt5PMldZQg0l/U6', 'USD', 'dark', 'en');

-- --------------------------------------------------------
-- Catégories — Alex
-- --------------------------------------------------------
INSERT INTO categories (id, user_id, name, type, color, icon) VALUES
(1, 1, 'Salaire',     'income',  '#00e5ff', 'wallet'),
(2, 1, 'Freelance',   'income',  '#3ddc97', 'laptop'),
(3, 1, 'Logement',    'expense', '#ff5f6d', 'home'),
(4, 1, 'Alimentation','expense', '#ffb703', 'cart'),
(5, 1, 'Transport',   'expense', '#8892ff', 'car'),
(6, 1, 'Loisirs',     'expense', '#ff8fb1', 'star');

-- --------------------------------------------------------
-- Catégories — Sam
-- --------------------------------------------------------
INSERT INTO categories (id, user_id, name, type, color, icon) VALUES
(7,  2, 'Salary',      'income',  '#00e5ff', 'wallet'),
(8,  2, 'Rent',        'expense', '#ff5f6d', 'home'),
(9,  2, 'Groceries',   'expense', '#ffb703', 'cart'),
(10, 2, 'Subscriptions','expense','#8892ff', 'repeat');

-- --------------------------------------------------------
-- Transactions — Alex (3 derniers mois)
-- --------------------------------------------------------
INSERT INTO transactions (user_id, category_id, type, amount, label, occurred_on) VALUES
(1, 1, 'income',  2800.00, 'Salaire Août',          CURDATE() - INTERVAL 3 DAY),
(1, 1, 'income',  2800.00, 'Salaire Juillet',        CURDATE() - INTERVAL 33 DAY),
(1, 2, 'income',  450.00,  'Mission freelance',      CURDATE() - INTERVAL 10 DAY),
(1, 3, 'expense', 950.00,  'Loyer',                  CURDATE() - INTERVAL 2 DAY),
(1, 4, 'expense', 62.40,   'Courses Carrefour',      CURDATE() - INTERVAL 4 DAY),
(1, 4, 'expense', 38.90,   'Courses Bio',            CURDATE() - INTERVAL 12 DAY),
(1, 5, 'expense', 75.00,   'Essence',                CURDATE() - INTERVAL 6 DAY),
(1, 5, 'expense', 49.00,   'Pass Navigo',            CURDATE() - INTERVAL 20 DAY),
(1, 6, 'expense', 120.00,  'Cinéma + resto',         CURDATE() - INTERVAL 8 DAY),
(1, 3, 'expense', 950.00,  'Loyer',                  CURDATE() - INTERVAL 32 DAY),
(1, 4, 'expense', 210.00,  'Courses mensuelles',     CURDATE() - INTERVAL 40 DAY);

-- --------------------------------------------------------
-- Transactions — Sam
-- --------------------------------------------------------
INSERT INTO transactions (user_id, category_id, type, amount, label, occurred_on) VALUES
(2, 7,  'income',  3200.00, 'August Salary',         CURDATE() - INTERVAL 5 DAY),
(2, 8,  'expense', 1100.00, 'Rent',                  CURDATE() - INTERVAL 4 DAY),
(2, 9,  'expense', 340.00,  'Groceries',             CURDATE() - INTERVAL 9 DAY),
(2, 10, 'expense', 45.00,   'Streaming + Cloud',     CURDATE() - INTERVAL 15 DAY);

-- --------------------------------------------------------
-- Objectifs — Alex
-- --------------------------------------------------------
INSERT INTO goals (id, user_id, title, target_amount, current_amount, target_date, status) VALUES
(1, 1, 'Fonds d''urgence', 5000.00, 3200.00, CURDATE() + INTERVAL 4 MONTH, 'active'),
(2, 1, 'Voyage Japon',     2500.00, 2500.00, CURDATE() - INTERVAL 5 DAY,   'achieved');

INSERT INTO goal_deposits (goal_id, amount, deposited_on) VALUES
(1, 1000.00, CURDATE() - INTERVAL 60 DAY),
(1, 1200.00, CURDATE() - INTERVAL 30 DAY),
(1, 1000.00, CURDATE() - INTERVAL 5 DAY),
(2, 2500.00, CURDATE() - INTERVAL 5 DAY);

-- --------------------------------------------------------
-- Objectifs — Sam
-- --------------------------------------------------------
INSERT INTO goals (id, user_id, title, target_amount, current_amount, target_date, status) VALUES
(3, 2, 'New laptop', 1800.00, 600.00, CURDATE() + INTERVAL 2 MONTH, 'active');

INSERT INTO goal_deposits (goal_id, amount, deposited_on) VALUES
(3, 600.00, CURDATE() - INTERVAL 10 DAY);

-- --------------------------------------------------------
-- Tâches — Alex
-- --------------------------------------------------------
INSERT INTO tasks (user_id, title, due_date, priority, status) VALUES
(1, 'Payer le loyer',   CURDATE(),                  'high',   'pending'),
(1, 'Appeler la banque',CURDATE() + INTERVAL 1 DAY, 'medium', 'pending'),
(1, 'Faire les courses',CURDATE(),                  'low',    'pending'),
(1, 'Déclarer impôts',  CURDATE() + INTERVAL 15 DAY,'high',   'pending'),
(1, 'Réserver vol Japon',CURDATE() - INTERVAL 3 DAY,'medium', 'done');

-- --------------------------------------------------------
-- Tâches — Sam
-- --------------------------------------------------------
INSERT INTO tasks (user_id, title, due_date, priority, status) VALUES
(2, 'Renew insurance',  CURDATE() + INTERVAL 3 DAY, 'high', 'pending'),
(2, 'Tidy the desk',    CURDATE(),                  'low',  'done');
