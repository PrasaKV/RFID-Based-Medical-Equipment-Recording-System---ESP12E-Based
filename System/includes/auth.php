<?php
if (session_status() === PHP_SESSION_NONE) session_start();

function requireLogin(): void {
    if (empty($_SESSION['user_id'])) {
        header('Location: /login.php');
        exit();
    }
}

function requireAdmin(): void {
    requireLogin();
    if (($_SESSION['role'] ?? '') !== 'admin') {
        header('Location: /dashboard.php');
        exit();
    }
}

function currentUser(): array {
    return [
        'user_id'  => $_SESSION['user_id']  ?? null,
        'username' => $_SESSION['username'] ?? 'Guest',
        'role'     => $_SESSION['role']     ?? 'engineer',
    ];
}

function isAdmin(): bool {
    return ($_SESSION['role'] ?? '') === 'admin';
}

function isEngineer(): bool {
    return ($_SESSION['role'] ?? '') === 'engineer';
}

function isTechnician(): bool {
    return ($_SESSION['role'] ?? '') === 'technician';
}

function isLoggedIn(): bool {
    return !empty($_SESSION['user_id']);
}

// ── Permission Helpers ────────────────────────────────────────────────────
// canEdit: admin and engineer can edit records
function canEdit(): bool {
    $role = $_SESSION['role'] ?? '';
    return in_array($role, ['admin', 'engineer']);
}

// canDelete: only admin can delete records
function canDelete(): bool {
    return isAdmin();
}

// requireCanEdit: enforce edit permission or return JSON error
function requireCanEdit(): void {
    if (!canEdit()) {
        http_response_code(403);
        echo json_encode(['error' => 'Permission denied. Only admin and engineer roles can edit records.']);
        exit();
    }
}

// requireCanDelete: enforce delete permission or return JSON error
function requireCanDelete(): void {
    if (!canDelete()) {
        http_response_code(403);
        echo json_encode(['error' => 'Permission denied. Only admin can delete records.']);
        exit();
    }
}
