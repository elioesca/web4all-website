DROP DATABASE IF EXISTS web4all;
CREATE DATABASE web4all CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE web4all;


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
('Cybersécurité');


INSERT INTO user (last_name, first_name, email, password, phone_number, is_valid) VALUES
('Admin', 'Super', 'admin@web4all.local', '$2y$10$sumSMqfg8Urezg2EfOife.atQuhvl5nhQmrN1mrFyvLw1wLKJC22m', '0600000001', TRUE),
('Martin', 'Paul', 'pilot@web4all.local', '$2y$10$sumSMqfg8Urezg2EfOife.atQuhvl5nhQmrN1mrFyvLw1wLKJC22m', '0600000002', TRUE),
('Dupont', 'Lina', 'student@web4all.local', '$2y$10$sumSMqfg8Urezg2EfOife.atQuhvl5nhQmrN1mrFyvLw1wLKJC22m', '0600000003', TRUE);

INSERT INTO administrator (user_id) VALUES (1);
INSERT INTO pilot (user_id) VALUES (2);
INSERT INTO student (user_id, search_status_id, promotion_id) VALUES (3, 1, 2);

INSERT INTO pilot_promotion (pilot_user_id, promotion_id) VALUES
(2, 2);

INSERT INTO company (name, description, activity_sector, email, phone_number, is_valid) VALUES
('Capgemini', 'Entreprise de services du numérique', 'Informatique', 'contact@capgemini.fr', '0102030405', TRUE),
('Sopra Steria', 'Entreprise spécialisée en conseil et services numériques', 'Informatique', 'contact@soprasteria.fr', '0102030406', TRUE);

INSERT INTO offer (available_places, title, description, duration, salary, publication_date, is_valid, company_id, created_by_user_id, offer_type_id) VALUES
(2, 'Stage Développeur Web', 'Développement PHP MVC', 8, 650.00, NOW(), TRUE, 1, 1, 1),
(1, 'Stage Réseaux', 'Administration systèmes et réseaux', 10, 700.00, NOW(), TRUE, 2, 2, 1);

INSERT INTO offer_skill (offer_id, skill_id) VALUES
(1, 1),
(1, 2),
(1, 3),
(1, 5),
(2, 7);

INSERT INTO wishlist (student_user_id, offer_id) VALUES
(3, 1);

INSERT INTO application (application_date, cv_path, cover_letter_path, application_status_id, student_user_id, offer_id) VALUES
(NOW(), 'uploads/cv/cv_lina_dupont.pdf', 'uploads/letters/lm_lina_dupont.pdf', 1, 3, 1);

INSERT INTO company_review (company_id, user_id, rating, review) VALUES
(1, 1, 5, 'Très bonne entreprise pour un stage.'),
(2, 2, 4, 'Bon suivi des stagiaires.');