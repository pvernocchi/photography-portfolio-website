-- Migration: Redesign photo management for many-to-many image ↔ category relationship
-- Images are stored once, assigned to multiple galleries (categories)

-- 1. Create join table for many-to-many relationship
CREATE TABLE image_category (
    image_id INT UNSIGNED NOT NULL,
    category_id INT UNSIGNED NOT NULL,
    sort_order INT UNSIGNED DEFAULT 0,
    PRIMARY KEY (image_id, category_id),
    FOREIGN KEY (image_id) REFERENCES images(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
    INDEX idx_category_sort (category_id, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Migrate existing data: copy current image→category assignments into the join table
INSERT INTO image_category (image_id, category_id, sort_order)
SELECT id, category_id, sort_order FROM images;

-- 3. Drop the old foreign key and column from images
-- NOTE: Find the actual FK constraint name with:
--   SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE
--   WHERE TABLE_NAME = 'images' AND COLUMN_NAME = 'category_id';
-- Then run: ALTER TABLE images DROP FOREIGN KEY <constraint_name>;
ALTER TABLE images DROP COLUMN category_id;

-- 4. Drop the old sort_order from images (now lives in join table)
ALTER TABLE images DROP COLUMN sort_order;

-- NOTE: After running this migration, move image files from
-- storage/{originals,thumbnails,display}/{category_id}/{filename}
-- to storage/{originals,thumbnails,display}/{filename}
