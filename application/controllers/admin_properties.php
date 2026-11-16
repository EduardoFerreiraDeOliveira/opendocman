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

// Admin Properties: list, create, edit and delete properties and set who can see them.

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['uid'])) {
    redirect_visitor();
}

$pdo = $GLOBALS['pdo'];

$user_obj = new User($_SESSION['uid'], $pdo);
if (!$user_obj->isAdmin()) {
    header('Location: error?ec=4');
    exit;
}

$L = property_labels();
$prefix = $GLOBALS['CONFIG']['db_prefix'];
$propPerms = new PropertyPerms($pdo);

// Rights a permission row can have. 0 means "no specific rights" (row not stored).
$rights_options = [
    ['value' => 0, 'label' => $L['no_rights']],
    ['value' => 1, 'label' => $L['view']],
    ['value' => 2, 'label' => $L['read']],
    ['value' => 3, 'label' => $L['write']],
    ['value' => 4, 'label' => $L['admin_right']],
    ['value' => -1, 'label' => $L['forbidden']],
];
$allowed_rights = [0, 1, 2, 3, 4, -1];

/**
 * Turn the posted perm_dept[] / perm_user[] arrays into a permission template.
 */
$parsePerms = function (array $post) use ($allowed_rights): array {
    $perms = [];
    foreach ((array) ($post['perm_dept'] ?? []) as $deptId => $rights) {
        $rights = (int) $rights;
        if ((int) $deptId > 0 && in_array($rights, $allowed_rights, true) && $rights !== 0) {
            $perms[] = ['dept_id' => (int) $deptId, 'user_id' => 0, 'rights' => $rights];
        }
    }
    foreach ((array) ($post['perm_user'] ?? []) as $userId => $rights) {
        $rights = (int) $rights;
        if ((int) $userId > 0 && in_array($rights, $allowed_rights, true) && $rights !== 0) {
            $perms[] = ['dept_id' => 0, 'user_id' => (int) $userId, 'rights' => $rights];
        }
    }
    return $perms;
};

$redirectWith = function (string $message): void {
    header('Location: admin_properties?last_message=' . urlencode($message));
    exit;
};

// ---------------------------------------------------------------- POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($GLOBALS['csrf']) && !$GLOBALS['csrf']->validateToken($_POST)) {
        header('Location: error?ec=0');
        exit;
    }

    $postAction = $_POST['action'] ?? '';
    $postId = (int) ($_POST['id'] ?? 0);

    if ($postAction === 'delete') {
        if ($postId > 0 && Property::getById($pdo, $postId) !== null) {
            Property::delete($pdo, $postId);
            $redirectWith($L['deleted']);
        }
        $redirectWith($L['error_not_found']);
    }

    if ($postAction === 'save') {
        [$clean, $errors] = Property::validate($_POST);

        if (!isset($errors['reference']) && Property::referenceExists($pdo, $clean['reference'], $postId)) {
            $errors['reference'] = 'property_error_reference_exists';
        }
        if ($postId > 0 && Property::getById($pdo, $postId) === null) {
            $redirectWith($L['error_not_found']);
        }

        if (count($errors) === 0) {
            if ($postId > 0) {
                Property::update($pdo, $postId, $clean);
            } else {
                $postId = Property::create($pdo, $clean, (int) $_SESSION['uid']);
            }
            $propPerms->saveTemplate($postId, $parsePerms($_POST));
            $redirectWith($L['saved']);
        }

        // Validation failed: show the form again with what the admin typed.
        $form_error = [];
        foreach ($errors as $field => $key) {
            $form_error[$field] = pmsg($key, $key);
        }
        $form_property = $_POST;
        $form_property['id'] = $postId;
        $form_perms = $parsePerms($_POST);
        $mode = 'form';
    }
}

// ---------------------------------------------------------------- GET routing
$last_message = (isset($_REQUEST['last_message']) ? $_REQUEST['last_message'] : '');
if (!isset($mode)) {
    $action = $_GET['action'] ?? 'list';
    $mode = ($action === 'new' || $action === 'edit') ? 'form' : 'list';
}

$csrf_data = $GLOBALS['csrf']->getTokenForTemplate('/admin_properties');
$GLOBALS['smarty']->assign('csrf_token_field', $csrf_data['field']);

draw_header($L['properties'], $last_message);

$GLOBALS['smarty']->assign('L', $L);
$GLOBALS['smarty']->assign('mode', $mode);

if ($mode === 'form') {
    if (!isset($form_property)) {
        $editId = (int) ($_GET['id'] ?? 0);
        if (($_GET['action'] ?? '') === 'edit' && $editId > 0) {
            $form_property = Property::getById($pdo, $editId);
            if ($form_property === null) {
                $redirectWith($L['error_not_found']);
            }
            $form_perms = $propPerms->getTemplate($editId);
        } else {
            $form_property = ['id' => 0, 'type' => 'apartment', 'status' => 'for_sale'];
            $form_perms = [];
        }
        $form_error = [];
    }

    // Every field the template reads must exist, so it never touches an undefined key.
    $form_property = array_merge(
        [
            'id' => 0, 'reference' => '', 'address' => '', 'city' => '', 'type' => 'apartment',
            'price' => '', 'status' => 'for_sale', 'owner_name' => '', 'description' => '',
        ],
        array_map(function ($v) {
            return $v === null ? '' : $v;
        }, $form_property)
    );
    $form_error = array_merge(['reference' => '', 'address' => '', 'price' => ''], $form_error);

    $deptRights = [];
    $userRights = [];
    foreach ($form_perms as $perm) {
        if ((int) $perm['dept_id'] > 0) {
            $deptRights[(int) $perm['dept_id']] = (int) $perm['rights'];
        } elseif ((int) $perm['user_id'] > 0) {
            $userRights[(int) $perm['user_id']] = (int) $perm['rights'];
        }
    }

    $departments = [];
    foreach (Department::getAllDepartments($pdo) as $dept) {
        $departments[] = [
            'id' => (int) $dept['id'],
            'name' => $dept['name'],
            'rights' => $deptRights[(int) $dept['id']] ?? 0,
        ];
    }

    $usersStmt = $pdo->query("SELECT id, last_name, first_name FROM {$prefix}user ORDER BY last_name, first_name");
    $users = [];
    foreach ($usersStmt->fetchAll(PDO::FETCH_ASSOC) as $u) {
        $users[] = [
            'id' => (int) $u['id'],
            'name' => trim($u['last_name'] . ', ' . $u['first_name']),
            'rights' => $userRights[(int) $u['id']] ?? 0,
        ];
    }

    $types = [];
    foreach (array_keys(Property::TYPES) as $code) {
        $types[] = ['code' => $code, 'label' => Property::label('type', $code)];
    }
    $statuses = [];
    foreach (array_keys(Property::STATUSES) as $code) {
        $statuses[] = ['code' => $code, 'label' => Property::label('status', $code)];
    }

    $GLOBALS['smarty']->assign('p', $form_property);
    $GLOBALS['smarty']->assign('form_error', $form_error);
    $GLOBALS['smarty']->assign('departments', $departments);
    $GLOBALS['smarty']->assign('users', $users);
    $GLOBALS['smarty']->assign('types', $types);
    $GLOBALS['smarty']->assign('statuses', $statuses);
    $GLOBALS['smarty']->assign('rights_options', $rights_options);
} else {
    $rows = Property::getAllWithCounts($pdo);
    foreach ($rows as &$row) {
        $row['type_label'] = Property::label('type', $row['type']);
        $row['status_label'] = Property::label('status', $row['status']);
        $row['price_text'] = $row['price'] === null ? '' : number_format((float) $row['price'], 2, ',', ' ');
    }
    unset($row);
    $GLOBALS['smarty']->assign('properties', $rows);
}

$GLOBALS['smarty']->assign('active_admin', 'properties');
ob_start();
display_smarty_template('admin_properties.tpl');
$GLOBALS['smarty']->assign('content', ob_get_clean());
display_smarty_template('_admin_content.tpl');

draw_footer();
