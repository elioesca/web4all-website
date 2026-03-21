DROP DATABASE IF EXISTS web4all;
CREATE DATABASE web4all CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE web4all;

SET NAMES utf8mb4;

CREATE TABLE user (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    last_name VARCHAR(50) NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    phone_number VARCHAR(50),
    is_valid BOOLEAN NOT NULL DEFAULT TRUE,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE administrator (
    user_id INT PRIMARY KEY,
    CONSTRAINT fk_administrator_user
        FOREIGN KEY (user_id) REFERENCES user(user_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
);

CREATE TABLE pilot (
    user_id INT PRIMARY KEY,
    CONSTRAINT fk_pilot_user
        FOREIGN KEY (user_id) REFERENCES user(user_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
);

CREATE TABLE promotion (
    promotion_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE
);

CREATE TABLE search_status (
    search_status_id INT AUTO_INCREMENT PRIMARY KEY,
    status VARCHAR(50) NOT NULL UNIQUE
);

CREATE TABLE student (
    user_id INT PRIMARY KEY,
    search_status_id INT NOT NULL,
    promotion_id INT NOT NULL,
    CONSTRAINT fk_student_user
        FOREIGN KEY (user_id) REFERENCES user(user_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT fk_student_search_status
        FOREIGN KEY (search_status_id) REFERENCES search_status(search_status_id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE,
    CONSTRAINT fk_student_promotion
        FOREIGN KEY (promotion_id) REFERENCES promotion(promotion_id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE
);

CREATE TABLE pilot_promotion (
    pilot_user_id INT NOT NULL,
    promotion_id INT NOT NULL,
    PRIMARY KEY (pilot_user_id, promotion_id),
    CONSTRAINT fk_pilot_promotion_pilot
        FOREIGN KEY (pilot_user_id) REFERENCES pilot(user_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT fk_pilot_promotion_promotion
        FOREIGN KEY (promotion_id) REFERENCES promotion(promotion_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
);

CREATE TABLE company (
    company_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description VARCHAR(1000),
    activity_sector VARCHAR(100),
    email VARCHAR(150),
    phone_number VARCHAR(50),
    is_valid BOOLEAN NOT NULL DEFAULT TRUE,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE offer_type (
    offer_type_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE
);

CREATE TABLE offer (
    offer_id INT AUTO_INCREMENT PRIMARY KEY,
    available_places SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    title VARCHAR(150) NOT NULL,
    description VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    duration INT NOT NULL,
    salary DECIMAL(10,2) DEFAULT NULL,
    publication_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    is_valid BOOLEAN NOT NULL DEFAULT TRUE,
    company_id INT NOT NULL,
    created_by_user_id INT NOT NULL,
    offer_type_id INT NOT NULL,
    CONSTRAINT fk_offer_company
        FOREIGN KEY (company_id) REFERENCES company(company_id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE,
    CONSTRAINT fk_offer_created_by_user
        FOREIGN KEY (created_by_user_id) REFERENCES user(user_id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE,
    CONSTRAINT fk_offer_type
        FOREIGN KEY (offer_type_id) REFERENCES offer_type(offer_type_id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE
);

CREATE TABLE skill (
    skill_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE
);

CREATE TABLE offer_skill (
    offer_id INT NOT NULL,
    skill_id INT NOT NULL,
    PRIMARY KEY (offer_id, skill_id),
    CONSTRAINT fk_offer_skill_offer
        FOREIGN KEY (offer_id) REFERENCES offer(offer_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT fk_offer_skill_skill
        FOREIGN KEY (skill_id) REFERENCES skill(skill_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
);

CREATE TABLE application_status (
    application_status_id INT AUTO_INCREMENT PRIMARY KEY,
    status VARCHAR(50) NOT NULL UNIQUE
);

CREATE TABLE application (
    application_id INT AUTO_INCREMENT PRIMARY KEY,
    application_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    cv_path VARCHAR(255) NOT NULL,
    cover_letter_path VARCHAR(255) NOT NULL,
    application_status_id INT NOT NULL,
    student_user_id INT NOT NULL,
    offer_id INT NOT NULL,
    CONSTRAINT uq_application_student_offer UNIQUE (student_user_id, offer_id),
    CONSTRAINT fk_application_status
        FOREIGN KEY (application_status_id) REFERENCES application_status(application_status_id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE,
    CONSTRAINT fk_application_student
        FOREIGN KEY (student_user_id) REFERENCES student(user_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT fk_application_offer
        FOREIGN KEY (offer_id) REFERENCES offer(offer_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
);

CREATE TABLE wishlist (
    student_user_id INT NOT NULL,
    offer_id INT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (student_user_id, offer_id),
    CONSTRAINT fk_wishlist_student
        FOREIGN KEY (student_user_id) REFERENCES student(user_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT fk_wishlist_offer
        FOREIGN KEY (offer_id) REFERENCES offer(offer_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
);

CREATE TABLE company_review (
    company_review_id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    user_id INT NOT NULL,
    rating TINYINT UNSIGNED NOT NULL,
    review TEXT,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_company_review_user_company UNIQUE (user_id, company_id),
    CONSTRAINT chk_company_review_rating CHECK (rating BETWEEN 1 AND 5),
    CONSTRAINT fk_company_review_company
        FOREIGN KEY (company_id) REFERENCES company(company_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT fk_company_review_user
        FOREIGN KEY (user_id) REFERENCES user(user_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
);

CREATE INDEX idx_user_email ON user(email);
CREATE INDEX idx_company_name ON company(name);
CREATE INDEX idx_offer_title ON offer(title);
CREATE INDEX idx_offer_publication_date ON offer(publication_date);
CREATE INDEX idx_application_date ON application(application_date);

INSERT INTO search_status (status) VALUES
('En recherche'),
('En attente de réponses'),
('Stage trouvé');

INSERT INTO application_status (status) VALUES
('Envoyée'),
('Consultée'),
('Entretien'),
('Acceptée'),
('Refusée');

INSERT INTO offer_type (name) VALUES
('Stage'),
('Alternance'),
('CDD');

INSERT INTO promotion (name) VALUES
('A1'),
('A2'),
('A3'),
('A4'),
('A5');

INSERT INTO skill (name) VALUES
('PHP'),
('HTML'),
('CSS'),
('JavaScript'),
('SQL'),
('Gestion de projet'),
('Réseaux'),
('Cybersécurité'),
('Linux'),
('Symfony'),
('Docker'),
('Python'),
('Power BI'),
('Support utilisateurs');

INSERT INTO user (last_name, first_name, email, password, phone_number, is_valid) VALUES
('Admin', 'Super', 'admin@web4all.local', '$2y$10$sumSMqfg8Urezg2EfOife.atQuhvl5nhQmrN1mrFyvLw1wLKJC22m', '0600000001', TRUE),
('Martin', 'Paul', 'pilot@web4all.local', '$2y$10$sumSMqfg8Urezg2EfOife.atQuhvl5nhQmrN1mrFyvLw1wLKJC22m', '0600000002', TRUE),

('Dupont', 'Lina', 'student@web4all.local', '$2y$10$sumSMqfg8Urezg2EfOife.atQuhvl5nhQmrN1mrFyvLw1wLKJC22m', '0600000003', TRUE),
('Martin', 'Lucas', 'lucas.martin@web4all.local', '$2y$10$sumSMqfg8Urezg2EfOife.atQuhvl5nhQmrN1mrFyvLw1wLKJC22m', '0600000004', TRUE),
('Bernard', 'Emma', 'emma.bernard@web4all.local', '$2y$10$sumSMqfg8Urezg2EfOife.atQuhvl5nhQmrN1mrFyvLw1wLKJC22m', '0600000005', TRUE),
('Dubois', 'Hugo', 'hugo.dubois@web4all.local', '$2y$10$sumSMqfg8Urezg2EfOife.atQuhvl5nhQmrN1mrFyvLw1wLKJC22m', '0600000006', TRUE),
('Thomas', 'Chloé', 'chloe.thomas@web4all.local', '$2y$10$sumSMqfg8Urezg2EfOife.atQuhvl5nhQmrN1mrFyvLw1wLKJC22m', '0600000007', TRUE),
('Robert', 'Nathan', 'nathan.robert@web4all.local', '$2y$10$sumSMqfg8Urezg2EfOife.atQuhvl5nhQmrN1mrFyvLw1wLKJC22m', '0600000008', TRUE),
('Richard', 'Léa', 'lea.richard@web4all.local', '$2y$10$sumSMqfg8Urezg2EfOife.atQuhvl5nhQmrN1mrFyvLw1wLKJC22m', '0600000009', TRUE),
('Moreau', 'Louis', 'louis.moreau@web4all.local', '$2y$10$sumSMqfg8Urezg2EfOife.atQuhvl5nhQmrN1mrFyvLw1wLKJC22m', '0600000010', TRUE),
('Simon', 'Camille', 'camille.simon@web4all.local', '$2y$10$sumSMqfg8Urezg2EfOife.atQuhvl5nhQmrN1mrFyvLw1wLKJC22m', '0600000011', TRUE),
('Laurent', 'Noah', 'noah.laurent@web4all.local', '$2y$10$sumSMqfg8Urezg2EfOife.atQuhvl5nhQmrN1mrFyvLw1wLKJC22m', '0600000012', TRUE),
('Lefebvre', 'Sarah', 'sarah.lefebvre@web4all.local', '$2y$10$sumSMqfg8Urezg2EfOife.atQuhvl5nhQmrN1mrFyvLw1wLKJC22m', '0600000013', TRUE),
('Michel', 'Tom', 'tom.michel@web4all.local', '$2y$10$sumSMqfg8Urezg2EfOife.atQuhvl5nhQmrN1mrFyvLw1wLKJC22m', '0600000014', TRUE),
('Garcia', 'Inès', 'ines.garcia@web4all.local', '$2y$10$sumSMqfg8Urezg2EfOife.atQuhvl5nhQmrN1mrFyvLw1wLKJC22m', '0600000015', TRUE),
('David', 'Arthur', 'arthur.david@web4all.local', '$2y$10$sumSMqfg8Urezg2EfOife.atQuhvl5nhQmrN1mrFyvLw1wLKJC22m', '0600000016', TRUE),
('Fournier', 'Gabriel', 'gabriel.fournier@web4all.local', '$2y$10$sumSMqfg8Urezg2EfOife.atQuhvl5nhQmrN1mrFyvLw1wLKJC22m', '0600000017', TRUE),
('Roux', 'Ethan', 'ethan.roux@web4all.local', '$2y$10$sumSMqfg8Urezg2EfOife.atQuhvl5nhQmrN1mrFyvLw1wLKJC22m', '0600000018', TRUE),

('Morel', 'Julie', 'julie.morel@web4all.local', '$2y$10$sumSMqfg8Urezg2EfOife.atQuhvl5nhQmrN1mrFyvLw1wLKJC22m', '0611111111', TRUE),
('Lemoine', 'David', 'david.lemoine@web4all.local', '$2y$10$sumSMqfg8Urezg2EfOife.atQuhvl5nhQmrN1mrFyvLw1wLKJC22m', '0622222222', TRUE);

INSERT INTO administrator (user_id) VALUES (1);

INSERT INTO pilot (user_id) VALUES
(2),
(16),
(17);

INSERT INTO student (user_id, search_status_id, promotion_id) VALUES
(3, 1, 2),
(4, 1, 2),
(5, 2, 2),
(6, 3, 2),
(7, 1, 2),
(8, 2, 2),
(9, 1, 3),
(10, 1, 3),
(11, 2, 3),
(12, 3, 3),
(13, 1, 4),
(14, 2, 4),
(15, 3, 4);

INSERT INTO pilot_promotion (pilot_user_id, promotion_id) VALUES
(2, 2),
(2, 3),
(16, 4),
(17, 5);

INSERT INTO company (name, description, activity_sector, email, phone_number, is_valid) VALUES
('Capgemini', 'Entreprise de services du numérique spécialisée en transformation digitale, développement logiciel et conseil.', 'Informatique', 'contact@capgemini.fr', '0102030405', TRUE),
('Sopra Steria', 'Entreprise de conseil, services numériques et édition de logiciels pour les grands comptes.', 'Informatique', 'contact@soprasteria.fr', '0102030406', TRUE),
('Orange Business', 'Solutions numériques pour les entreprises : cloud, cybersécurité, réseaux et data.', 'Télécom & IT', 'contact@orange-business.fr', '0102030407', TRUE),
('OVHcloud', 'Acteur européen du cloud proposant hébergement, serveurs, stockage et solutions cloud.', 'Cloud', 'contact@ovhcloud.fr', '0102030408', TRUE),
('Atos', 'Prestataire de services informatiques en infrastructure, cybersécurité et transformation numérique.', 'Informatique', 'contact@atos.fr', '0102030409', TRUE),
('Worldline', 'Entreprise spécialisée dans les paiements et les services transactionnels numériques.', 'Fintech', 'contact@worldline.fr', '0102030410', TRUE);

INSERT INTO offer (available_places, title, description, content, duration, salary, publication_date, is_valid, company_id, created_by_user_id, offer_type_id) VALUES
(2, 'Stage Développeur Web PHP', 'Participation au développement d''une plateforme web en PHP MVC.', 'Vous rejoignez une équipe produit pour développer et maintenir une plateforme web interne. Missions : développement de nouvelles fonctionnalités, correction de bugs, optimisation SQL, participation aux tests et à la documentation technique.', 8, 650.00, NOW(), TRUE, 1, 1, 1),

(1, 'Stage Administrateur Systèmes et Réseaux', 'Administration systèmes, support et supervision réseau.', 'Vous intervenez sur l''administration des serveurs Linux, la supervision, le support de niveau 1 et 2 et la gestion des incidents réseau. Une appétence pour la cybersécurité est appréciée.', 10, 700.00, NOW(), TRUE, 2, 2, 1),

(2, 'Alternance Développeur Full Stack', 'Développement d''applications web modernes côté front et back.', 'Missions : développement front en JavaScript, intégration HTML/CSS, développement backend PHP, conception de bases de données SQL et participation aux revues de code.', 12, 1200.00, NOW(), TRUE, 3, 1, 2),

(1, 'Stage Data Analyst', 'Analyse de données, reporting et tableaux de bord.', 'Vous participez à la collecte, au nettoyage et à l''analyse des données. Création de tableaux de bord, indicateurs de suivi et rapports d''activité pour les équipes métiers.', 6, 800.00, NOW(), TRUE, 6, 1, 1),

(2, 'Stage DevOps Junior', 'CI/CD, conteneurisation et déploiement.', 'Vous accompagnez l''équipe infrastructure sur l''automatisation des déploiements, l''usage de Docker, la gestion des environnements et le suivi de la qualité logicielle.', 6, 900.00, NOW(), TRUE, 4, 2, 1),

(1, 'Alternance Support Informatique', 'Support utilisateurs, maintenance du parc et gestion des incidents.', 'Au sein de l''équipe support, vous assurez le suivi des tickets, l''assistance aux utilisateurs, la préparation des postes et la maintenance de premier niveau.', 12, 1100.00, NOW(), TRUE, 5, 2, 2),

(1, 'Stage Développeur Symfony', 'Développement backend avec Symfony et API REST.', 'Participation à la conception et au développement d''API REST, maintenance applicative, modélisation de base de données et écriture de tests.', 6, 750.00, NOW(), TRUE, 1, 1, 1),

(2, 'CDD Technicien Réseaux', 'Exploitation réseau et support technique.', 'Vous intervenez sur la configuration d''équipements réseau, la surveillance, le diagnostic d''incidents et la mise à jour de la documentation.', 4, 1800.00, NOW(), TRUE, 2, 2, 3),

(1, 'Stage Analyste Cybersécurité', 'Surveillance, analyse d''alertes et sensibilisation sécurité.', 'Missions : suivi des alertes de sécurité, revue des journaux, participation à la gestion des vulnérabilités et rédaction de procédures.', 6, 850.00, NOW(), TRUE, 3, 1, 1),

(2, 'Stage Développeur Backend PHP / SQL', 'Développement backend et optimisation de requêtes.', 'Vous développez des modules backend en PHP, améliorez les performances SQL et participez à la maintenance d''une application métier.', 8, 700.00, NOW(), TRUE, 6, 2, 1);

INSERT INTO offer_skill (offer_id, skill_id) VALUES
(1, 1), (1, 2), (1, 3), (1, 5),
(2, 7), (2, 9), (2, 8),
(3, 1), (3, 2), (3, 3), (3, 4), (3, 5),
(4, 5), (4, 12), (4, 13),
(5, 11), (5, 9), (5, 6),
(6, 14), (6, 6),
(7, 1), (7, 5), (7, 10),
(8, 7), (8, 9),
(9, 8), (9, 9),
(10, 1), (10, 5), (10, 10);

INSERT INTO wishlist (student_user_id, offer_id, created_at) VALUES
(3, 1, NOW()),
(3, 3, NOW()),
(4, 1, NOW()),
(4, 7, NOW()),
(5, 4, NOW()),
(7, 3, NOW()),
(8, 6, NOW()),
(9, 5, NOW()),
(10, 9, NOW()),
(11, 10, NOW()),
(13, 2, NOW()),
(14, 8, NOW());

INSERT INTO application (application_date, cv_path, cover_letter_path, application_status_id, student_user_id, offer_id) VALUES
(NOW(), 'uploads/cv/cv_lina_dupont.pdf', 'uploads/letters/lm_lina_dupont.pdf', 1, 3, 1),
(NOW(), 'uploads/cv/lucas_martin_cv.pdf', 'uploads/letters/lucas_martin_lm.pdf', 2, 4, 3),
(NOW(), 'uploads/cv/emma_bernard_cv.pdf', 'uploads/letters/emma_bernard_lm.pdf', 3, 5, 4),
(NOW(), 'uploads/cv/hugo_dubois_cv.pdf', 'uploads/letters/hugo_dubois_lm.pdf', 4, 6, 2),
(NOW(), 'uploads/cv/chloe_thomas_cv.pdf', 'uploads/letters/chloe_thomas_lm.pdf', 1, 7, 7),
(NOW(), 'uploads/cv/nathan_robert_cv.pdf', 'uploads/letters/nathan_robert_lm.pdf', 2, 8, 6),
(NOW(), 'uploads/cv/lea_richard_cv.pdf', 'uploads/letters/lea_richard_lm.pdf', 1, 9, 5),
(NOW(), 'uploads/cv/louis_moreau_cv.pdf', 'uploads/letters/louis_moreau_lm.pdf', 5, 10, 9),
(NOW(), 'uploads/cv/camille_simon_cv.pdf', 'uploads/letters/camille_simon_lm.pdf', 3, 11, 10),
(NOW(), 'uploads/cv/noah_laurent_cv.pdf', 'uploads/letters/noah_laurent_lm.pdf', 2, 12, 3),
(NOW(), 'uploads/cv/sarah_lefebvre_cv.pdf', 'uploads/letters/sarah_lefebvre_lm.pdf', 4, 13, 6),
(NOW(), 'uploads/cv/tom_michel_cv.pdf', 'uploads/letters/tom_michel_lm.pdf', 1, 14, 8),
(NOW(), 'uploads/cv/ines_garcia_cv.pdf', 'uploads/letters/ines_garcia_lm.pdf', 2, 15, 1);

INSERT INTO company_review (company_id, user_id, rating, review, created_at) VALUES
(1, 1, 5, 'Très bonne entreprise pour découvrir le développement web en environnement professionnel.', NOW()),
(2, 2, 4, 'Bon suivi des stagiaires et encadrement sérieux.', NOW()),
(3, 4, 5, 'Équipe accueillante et missions intéressantes sur des sujets actuels.', NOW()),
(4, 5, 4, 'Très bon environnement technique, surtout pour progresser sur le cloud.', NOW()),
(5, 7, 4, 'Encadrement professionnel et missions variées.', NOW()),
(6, 9, 5, 'Excellente expérience, bonnes responsabilités confiées rapidement.', NOW());