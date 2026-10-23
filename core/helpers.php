<?php
declare(strict_types=1);

/* ---------- Repli si l'extension mbstring est absente (certains hébergements) ---------- */
if (!function_exists('mb_strlen')) {
    function mb_strlen($texte, $enc = null)
    {
        return (int) preg_match_all('/./us', (string) $texte);
    }
}
if (!function_exists('mb_substr')) {
    function mb_substr($texte, $debut, $longueur = null, $enc = null)
    {
        $car = preg_split('//u', (string) $texte, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        return implode('', array_slice($car, $debut, $longueur));
    }
}
if (!function_exists('mb_strtoupper')) {
    function mb_strtoupper($texte, $enc = null)
    {
        return strtr(strtoupper((string) $texte), [
            'à' => 'À', 'â' => 'Â', 'ä' => 'Ä', 'é' => 'É', 'è' => 'È', 'ê' => 'Ê', 'ë' => 'Ë', 'î' => 'Î',
            'ï' => 'Ï', 'ô' => 'Ô', 'ö' => 'Ö', 'ù' => 'Ù', 'û' => 'Û', 'ü' => 'Ü', 'ç' => 'Ç', 'œ' => 'Œ',
        ]);
    }
}

/* ---------- Échappement et URLs ---------- */
function e($valeur): string
{
    return htmlspecialchars((string) $valeur, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function url(string $action = '', array $params = []): string
{
    $query = $action !== '' ? array_merge(['action' => $action], $params) : $params;
    return 'index.php' . ($query ? '?' . http_build_query($query) : '');
}

function redirect(string $action, array $params = []): void
{
    header('Location: ' . url($action, $params));
    exit;
}

/* ---------- Messages flash ---------- */
function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'msg' => $message];
}

function flashes(): array
{
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $messages;
}

/* ---------- Protection CSRF ---------- */
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function csrf_verify(): void
{
    $recu = (string) ($_POST['csrf'] ?? '');
    if (!hash_equals(csrf_token(), $recu)) {
        View::error(419, 'Session expirée', 'Le formulaire a expiré. Rechargez la page puis réessayez.');
    }
}

/* ---------- Saisie ---------- */
function input(string $cle, string $defaut = ''): string
{
    return trim((string) ($_POST[$cle] ?? $defaut));
}

function get_param(string $cle, string $defaut = ''): string
{
    return trim((string) ($_GET[$cle] ?? $defaut));
}

/* ---------- Formats ---------- */
function duree_minutes($minutes): string
{
    if ($minutes === null || $minutes === '') {
        return '-';
    }
    $m = (int) $minutes;
    if ($m < 1) {
        return "à l'instant";
    }
    if ($m < 60) {
        return $m . ' min';
    }
    if ($m < 1440) {
        return intdiv($m, 60) . ' h ' . str_pad((string) ($m % 60), 2, '0', STR_PAD_LEFT);
    }
    return intdiv($m, 1440) . ' j';
}

function date_fr(?string $date, bool $avecHeure = false): string
{
    if (!$date) {
        return '-';
    }
    $ts = strtotime($date);
    return $ts ? date($avecHeure ? 'd/m/Y H:i' : 'd/m/Y', $ts) : '-';
}

function initiales(array $user): string
{
    return mb_strtoupper(mb_substr((string) $user['prenom'], 0, 1) . mb_substr((string) $user['nom'], 0, 1));
}

/* ---------- Icônes (SVG en ligne, style trait) ---------- */
function icon(string $nom, int $taille = 18): string
{
    static $icones = [
        'dashboard' => '<rect x="3" y="3" width="7" height="9" rx="1"/><rect x="14" y="3" width="7" height="5" rx="1"/><rect x="14" y="12" width="7" height="9" rx="1"/><rect x="3" y="16" width="7" height="5" rx="1"/>',
        'users'     => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
        'bed'       => '<path d="M2 4v16"/><path d="M2 8h18a2 2 0 0 1 2 2v10"/><path d="M2 17h20"/><path d="M6 8v9"/>',
        'drop'      => '<path d="M12 22a7 7 0 0 0 7-7c0-2-1-3.9-3-5.5s-3.5-4-4-6.5c-.5 2.5-2 4.9-4 6.5C6 11.1 5 13 5 15a7 7 0 0 0 7 7z"/>',
        'pulse'     => '<path d="M22 12h-4l-3 9L9 3l-3 9H2"/>',
        'file'      => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M16 13H8"/><path d="M16 17H8"/><path d="M10 9H8"/>',
        'logout'    => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5"/><path d="M21 12H9"/>',
        'plus'      => '<path d="M12 5v14"/><path d="M5 12h14"/>',
        'search'    => '<circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>',
        'download'  => '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="m7 10 5 5 5-5"/><path d="M12 15V3"/>',
        'edit'      => '<path d="M17 3a2.85 2.85 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/>',
        'trash'     => '<path d="M3 6h18"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>',
        'menu'      => '<path d="M3 12h18"/><path d="M3 6h18"/><path d="M3 18h18"/>',
        'check'     => '<path d="M20 6 9 17l-5-5"/>',
        'x'         => '<path d="M18 6 6 18"/><path d="m6 6 12 12"/>',
        'alert'     => '<path d="m10.29 3.86-8.47 14.14A2 2 0 0 0 3.53 21h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><path d="M12 9v4"/><path d="M12 17h.01"/>',
        'clock'     => '<circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/>',
        'heart'     => '<path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/>',
        'lock'      => '<rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>',
        'user'      => '<path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
        'unlock'    => '<path d="M3 11h18v11H3z"/><path d="M7 11V7a5 5 0 0 1 9.9-1"/>',
    ];
    $contenu = $icones[$nom] ?? '';
    return '<svg class="icon" width="' . $taille . '" height="' . $taille . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" '
         . 'stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $contenu . '</svg>';
}

/* ---------- Badges ---------- */
function badge_urgence(string $valeur): string
{
    $classes = ['Absolue' => 'danger', 'Moyenne' => 'warning', 'Faible' => 'info'];
    return '<span class="badge badge-' . ($classes[$valeur] ?? 'neutral') . '">' . e($valeur) . '</span>';
}

function badge_statut(string $valeur): string
{
    $classes = ['En attente' => 'neutral', 'En prise en charge' => 'primary', 'Traité' => 'success'];
    return '<span class="badge badge-' . ($classes[$valeur] ?? 'neutral') . '">' . e($valeur) . '</span>';
}

function badge_ressource(string $valeur): string
{
    $classes = ['Disponible' => 'success', 'Occupé' => 'danger', 'En maintenance' => 'warning'];
    return '<span class="badge badge-' . ($classes[$valeur] ?? 'neutral') . '">' . e($valeur) . '</span>';
}

function badge_demande(string $valeur): string
{
    $classes = ['En attente' => 'warning', 'Satisfaite' => 'success', 'Refusée' => 'danger'];
    return '<span class="badge badge-' . ($classes[$valeur] ?? 'neutral') . '">' . e($valeur) . '</span>';
}

function groupe_pastille(?string $groupe): string
{
    return $groupe ? '<span class="groupe">' . e($groupe) . '</span>' : '<span class="muted">-</span>';
}

/* ---------- Export CSV : neutralise les formules Excel ---------- */
function csv_safe($valeur): string
{
    $texte = (string) $valeur;
    return ($texte !== '' && strpos("=+-@\t\r", $texte[0]) !== false) ? "'" . $texte : $texte;
}
