INSERT INTO users (name, email, password_hash)
VALUES (
    'Administração',
    'admin@indigoplateau.local',
    '$2y$10$4XO4JYBr6EY0bVhYE10qZezaQebbVO5nZl9P5u7CKEZ51KajJJuRm'
)
ON DUPLICATE KEY UPDATE name = VALUES(name);

INSERT INTO cards (name_en, name_pt, game, edition_id, rarity, image_source, image_value)
VALUES
    ('Bifur, Melodic Rider', NULL, 'magic', 'the-hobbit', 'Uncommon', 'upload', 'assets/images/bifur-melodic-rider.jpg'),
    ('Sol Ring', 'Anel Solar', 'magic', 'marvel-super-heroes-commander', 'Uncommon', 'upload', 'assets/images/sol-ring.jpg'),
    ('Fezandipiti ex', NULL, 'pokemon', 'shrouded-fable', 'Special Illustration Rare', 'upload', 'assets/images/fezandipiti-ex.png'),
    ('Pikachu ex', NULL, 'pokemon', 'surging-sparks', 'Special Illustration Rare', 'upload', 'assets/images/pikachu-ex.png'),
    ('Blue-Eyes White Dragon', 'Dragão Branco de Olhos Azuis', 'yugioh', 'legend-of-blue-eyes-white-dragon', 'Ultra Rare', 'upload', 'assets/images/blue-eyes-white-dragon.jpg'),
    ('Dark Magician', 'Mago Negro', 'yugioh', 'legend-of-blue-eyes-white-dragon', 'Ultra Rare', 'upload', 'assets/images/dark-magician.jpg')
ON DUPLICATE KEY UPDATE updated_at = CURRENT_TIMESTAMP;
