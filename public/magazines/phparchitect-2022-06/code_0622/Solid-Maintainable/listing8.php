CREATE TABLE ebooks (
        ebook_id INT PRIMARY KEY,
        title VARCHAR(255),
        content TEXT,
        price FLOAT
    )
CREATE TABLE orders (
        order_id VARCHAR(36) PRIMARY KEY, 
        email VARCHAR(255), 
        credit_card JSON, 
        related_ebook_ids JSON, 
        price FLOAT, 
        occurred_at TIMESTAMP
    )
CREATE TABLE promotions (
        email VARCHAR(255) PRIMARY KEY,
        amount_of_orders INT
    )