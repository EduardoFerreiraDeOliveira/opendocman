<?php
/*
 * Copyright (C) 2000-2025. Stephen Lawrence
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */

if (!defined('Property_class')) {
    define('Property_class', 'true', false);

    /**
     * A real-estate property. Documents link to a property through
     * data.property_id (0 = no property). Access follows the property's
     * permission template (property_perms), after category rules.
     */
    class Property
    {
        /** Allowed values, each with the English label used as fallback. */
        public const TYPES = [
            'apartment' => 'Apartment',
            'house' => 'House',
            'land' => 'Land',
            'commercial' => 'Commercial',
            'other' => 'Other',
        ];

        public const STATUSES = [
            'for_sale' => 'For sale',
            'for_rent' => 'For rent',
            'reserved' => 'Reserved',
            'sold' => 'Sold',
            'rented' => 'Rented',
        ];

        private static function table(): string
        {
            return $GLOBALS['CONFIG']['db_prefix'] . 'property';
        }

        /**
         * Validate and normalise user input.
         *
         * @param array $input raw form fields
         * @return array [cleanData, errors] where errors maps field => message key
         */
        public static function validate(array $input): array
        {
            $errors = [];
            $reference = trim((string) ($input['reference'] ?? ''));
            $address = trim((string) ($input['address'] ?? ''));
            $city = trim((string) ($input['city'] ?? ''));
            $type = (string) ($input['type'] ?? 'apartment');
            $status = (string) ($input['status'] ?? 'for_sale');
            $ownerName = trim((string) ($input['owner_name'] ?? ''));
            $description = trim((string) ($input['description'] ?? ''));
            $priceRaw = trim((string) ($input['price'] ?? ''));

            if ($reference === '') {
                $errors['reference'] = 'property_error_reference_required';
            }
            if ($address === '') {
                $errors['address'] = 'property_error_address_required';
            }
            if (!isset(self::TYPES[$type])) {
                $type = 'other';
            }
            if (!isset(self::STATUSES[$status])) {
                $status = 'for_sale';
            }

            $price = null;
            if ($priceRaw !== '') {
                // Accept "250000", "250000.50" and "250000,50"
                $normalised = str_replace(',', '.', str_replace(' ', '', $priceRaw));
                if (!is_numeric($normalised) || (float) $normalised < 0 || (float) $normalised > 9999999999.99) {
                    $errors['price'] = 'property_error_price';
                } else {
                    $price = round((float) $normalised, 2);
                }
            }

            return [[
                'reference' => mb_substr($reference, 0, 50),
                'address' => mb_substr($address, 0, 255),
                'city' => $city === '' ? null : mb_substr($city, 0, 100),
                'type' => $type,
                'status' => $status,
                'price' => $price,
                'owner_name' => $ownerName === '' ? null : mb_substr($ownerName, 0, 255),
                'description' => $description === '' ? null : $description,
            ], $errors];
        }

        public static function referenceExists(PDO $pdo, string $reference, int $exceptId = 0): bool
        {
            $stmt = $pdo->prepare('SELECT 1 FROM ' . self::table() . ' WHERE reference = :ref AND id <> :id LIMIT 1');
            $stmt->execute([':ref' => $reference, ':id' => $exceptId]);
            return $stmt->fetchColumn() !== false;
        }

        public static function getById(PDO $pdo, int $id): ?array
        {
            $stmt = $pdo->prepare('SELECT * FROM ' . self::table() . ' WHERE id = :id');
            $stmt->execute([':id' => $id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row === false ? null : $row;
        }

        /**
         * @param array $data output of validate()
         * @return int new property id
         */
        public static function create(PDO $pdo, array $data, int $createdBy): int
        {
            $stmt = $pdo->prepare(
                'INSERT INTO ' . self::table()
                . ' (reference, address, city, type, price, status, owner_name, description, created, created_by)'
                . ' VALUES (:reference, :address, :city, :type, :price, :status, :owner_name, :description, NOW(), :created_by)'
            );
            $stmt->execute([
                ':reference' => $data['reference'],
                ':address' => $data['address'],
                ':city' => $data['city'],
                ':type' => $data['type'],
                ':price' => $data['price'],
                ':status' => $data['status'],
                ':owner_name' => $data['owner_name'],
                ':description' => $data['description'],
                ':created_by' => $createdBy,
            ]);
            return (int) $pdo->lastInsertId();
        }

        public static function update(PDO $pdo, int $id, array $data): void
        {
            $stmt = $pdo->prepare(
                'UPDATE ' . self::table()
                . ' SET reference = :reference, address = :address, city = :city, type = :type,'
                . ' price = :price, status = :status, owner_name = :owner_name, description = :description'
                . ' WHERE id = :id'
            );
            $stmt->execute([
                ':reference' => $data['reference'],
                ':address' => $data['address'],
                ':city' => $data['city'],
                ':type' => $data['type'],
                ':price' => $data['price'],
                ':status' => $data['status'],
                ':owner_name' => $data['owner_name'],
                ':description' => $data['description'],
                ':id' => $id,
            ]);
        }

        /**
         * Delete a property. Its documents are kept and simply unlinked.
         */
        public static function delete(PDO $pdo, int $id): void
        {
            $prefix = $GLOBALS['CONFIG']['db_prefix'];
            $pdo->prepare("UPDATE {$prefix}data SET property_id = 0 WHERE property_id = :id")->execute([':id' => $id]);
            $pdo->prepare("DELETE FROM {$prefix}property_perms WHERE prop_id = :id")->execute([':id' => $id]);
            $pdo->prepare('DELETE FROM ' . self::table() . ' WHERE id = :id')->execute([':id' => $id]);
        }

        /**
         * Properties with their document counts.
         *
         * @param array|null $onlyIds restrict to these ids (null = all, [] = none)
         * @param array $filters search (text), status, city, type
         */
        public static function getAllWithCounts(PDO $pdo, ?array $onlyIds = null, array $filters = []): array
        {
            if ($onlyIds !== null && count($onlyIds) === 0) {
                return [];
            }
            $prefix = $GLOBALS['CONFIG']['db_prefix'];
            $where = [];
            $params = [];

            if ($onlyIds !== null) {
                $ph = [];
                foreach (array_values($onlyIds) as $i => $pid) {
                    $ph[] = ':id' . $i;
                    $params[':id' . $i] = (int) $pid;
                }
                $where[] = 'p.id IN (' . implode(',', $ph) . ')';
            }
            if (!empty($filters['status']) && isset(self::STATUSES[$filters['status']])) {
                $where[] = 'p.status = :status';
                $params[':status'] = $filters['status'];
            }
            if (!empty($filters['type']) && isset(self::TYPES[$filters['type']])) {
                $where[] = 'p.type = :type';
                $params[':type'] = $filters['type'];
            }
            if (!empty($filters['city'])) {
                $where[] = 'p.city = :city';
                $params[':city'] = $filters['city'];
            }
            if (!empty($filters['search'])) {
                $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], trim($filters['search'])) . '%';
                $where[] = '(p.address LIKE :q1 OR p.reference LIKE :q2 OR p.city LIKE :q3)';
                $params[':q1'] = $like;
                $params[':q2'] = $like;
                $params[':q3'] = $like;
            }

            $sql = "SELECT p.*, (SELECT COUNT(*) FROM {$prefix}data d WHERE d.property_id = p.id AND d.publishable = 1) AS doc_count,"
                . " (SELECT MAX(d2.created) FROM {$prefix}data d2 WHERE d2.property_id = p.id) AS last_document"
                . ' FROM ' . self::table() . ' p'
                . (count($where) ? ' WHERE ' . implode(' AND ', $where) : '')
                . ' ORDER BY p.created DESC, p.id DESC';
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        /** Distinct cities, for the home page filter. */
        public static function getCities(PDO $pdo): array
        {
            $stmt = $pdo->query('SELECT DISTINCT city FROM ' . self::table() . " WHERE city IS NOT NULL AND city <> '' ORDER BY city");
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        }

        /**
         * Ids of the properties a user may see: admins see all (null = no
         * restriction); everyone else sees the properties whose permission
         * template grants them (or their department) at least $minRights
         * (1 = view, 3 = write).
         * A user row overrides the department row for the same property.
         */
        public static function getVisibleIds(PDO $pdo, int $uid, int $deptId, bool $isAdmin, int $minRights = 1): ?array
        {
            if ($isAdmin) {
                return null;
            }
            $prefix = $GLOBALS['CONFIG']['db_prefix'];
            $sql = "SELECT DISTINCT pp.prop_id FROM {$prefix}property_perms pp
                    WHERE pp.rights >= :min
                      AND (
                        pp.user_id = :uid
                        OR (pp.dept_id = :dept AND :dept > 0
                            AND NOT EXISTS (
                                SELECT 1 FROM {$prefix}property_perms pu
                                WHERE pu.prop_id = pp.prop_id AND pu.user_id = :uid
                            ))
                      )";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':uid' => $uid, ':dept' => $deptId, ':min' => $minRights]);
            return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
        }

        /**
         * Documents of a property that the user may at least view, using the
         * normal permission rules (file, category, then property).
         *
         * @return array rows: id, realname, created, description, category_name, authority
         */
        public static function getDocuments(PDO $pdo, int $propertyId, int $uid): array
        {
            $prefix = $GLOBALS['CONFIG']['db_prefix'];
            $stmt = $pdo->prepare(
                "SELECT d.id, d.realname, d.created, d.description, c.name AS category_name
                 FROM {$prefix}data d
                 LEFT JOIN {$prefix}category c ON c.id = d.category
                 WHERE d.property_id = :pid AND d.publishable = 1
                 ORDER BY c.name, d.created DESC"
            );
            $stmt->execute([':pid' => $propertyId]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $perm = new UserPermission($uid, $pdo);
            $visible = [];
            foreach ($rows as $row) {
                $authority = (int) $perm->getAuthority((int) $row['id']);
                if ($authority >= 1) {
                    $row['authority'] = $authority;
                    $visible[] = $row;
                }
            }
            return $visible;
        }

        /** Label for a type or status code, translated when a string exists. */
        public static function label(string $group, string $code): string
        {
            $map = $group === 'type' ? self::TYPES : self::STATUSES;
            $default = $map[$code] ?? $code;
            $key = 'property_' . $group . '_' . $code;
            return isset($GLOBALS['lang'][$key]) ? (string) $GLOBALS['lang'][$key] : $default;
        }
    }
}
