-- Assuming a relational database like MySQL/PostgreSQL

-- Companies Table
CREATE TABLE companies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    type ENUM('TBK', 'Non-TBK') NOT NULL,
    address TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Engagements Table (Each company can have multiple engagements, one per audit year/period)
CREATE TABLE engagements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    year INT NOT NULL,
    status ENUM('Planning', 'Fieldwork', 'Review', 'Finalized') DEFAULT 'Planning',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies(id)
);
