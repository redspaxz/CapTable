<?php
/**
 * One-shot helper: wrap recurring UI labels with <?= __('…') ?> across views.
 * Delimiter-anchored (only inside >…< HTML text) so dynamic PHP and legal
 * document bodies are never touched. Longest-first replacement avoids
 * partial overlaps (e.g. "Total par value" before "Total").
 */
$root = __DIR__ . '/../app/Views';
$skip = [
    'layouts/app.php', 'dashboard/index.php', 'auth/login.php', 'settings/index.php',
    // Generated legal documents stay in their document language (English).
    'documents/certificate.php', 'documents/deed.php', 'documents/minutes.php',
];

$pairs = [
    'Shares' => 'Titres',
    'Shareholder' => 'Actionnaire',
    'Class' => 'Catégorie',
    'Cancel' => 'Annuler',
    'Type' => 'Type',
    'Total' => 'Total',
    'Date' => 'Date',
    'Par value' => 'Valeur nominale',
    'Notes' => 'Notes',
    'Holder' => 'Titulaire',
    'Yes' => 'Oui',
    'Waterfall' => 'Waterfall',
    'Vested' => 'Acquis',
    'Reference' => 'Référence',
    'Preference' => 'Préférence',
    'Participating remainder' => 'Solde participant',
    'Open' => 'Ouvrir',
    'Nationality' => 'Nationalité',
    'Granted' => 'Attribuées',
    'Exercised' => 'Exercées',
    'Exercisable' => 'Exerçables',
    'Current cap table' => 'Cap table actuel',
    'Calculate' => 'Calculer',
    'Beneficiary' => 'Bénéficiaire',
    'Approve' => 'Approuver',
    'Reject' => 'Rejeter',
    'Voting weight' => 'Poids de vote',
    'Voting rights' => 'Droits de vote',
    'Votes' => 'Voix',
    'Vesting schedule' => 'Calendrier de vesting',
    'Valuation cap (XAF)' => 'Plafond de valorisation (XAF)',
    'Tranche' => 'Tranche',
    'Total voting rights' => 'Droits de vote totaux',
    'Total value' => 'Valeur totale',
    'Total received' => 'Total perçu',
    'Total par value' => 'Valeur nominale totale',
    'Total distributed' => 'Total distribué',
    'The register is empty.' => 'Registre vide.',
    'Status' => 'Statut',
    'Shareholders' => 'Actionnaires',
    'Share transfers' => 'Cessions de titres',
    'Share issuances' => 'Émissions de titres',
    'Share classes' => 'Catégories de titres',
    'Strike' => "Prix d'exercice",
    'Print' => 'Imprimer',
    'Save' => 'Enregistrer',
    'Back' => 'Retour',
    'History' => 'Historique',
    'Export CSV' => 'Export CSV',
    'Quantity' => 'Quantité',
    'Name' => 'Nom',
    'Email' => 'E-mail',
    'Phone' => 'Téléphone',
    'Address' => 'Adresse',
    'Actions' => 'Actions',
];

uksort($pairs, fn($a, $b) => strlen($b) <=> strlen($a));

$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));
$wrapped = [];
foreach ($it as $file) {
    if ($file->getExtension() !== 'php') {
        continue;
    }
    $rel = str_replace('\\', '/', ltrim(str_replace($root, '', $file->getPathname()), '/\\'));
    if (in_array($rel, $skip, true)) {
        continue;
    }
    $s = $orig = file_get_contents($file->getPathname());
    foreach ($pairs as $en => $fr) {
        $wrappedLabel = '><?= __(\'' . str_replace("'", "\\'", $en) . '\') ?><';
        $s = str_replace('>' . $en . '<', $wrappedLabel, $s);
    }
    if ($s !== $orig) {
        file_put_contents($file->getPathname(), $s);
        $wrapped[] = $rel;
    }
}
echo "wrapped " . count($wrapped) . " files:\n" . implode("\n", $wrapped) . "\n";

// Merge the used translations into the FR dictionary (idempotent).
$dictFile = __DIR__ . '/../app/lang/fr.php';
$dict = (array) require $dictFile;
$added = 0;
foreach ($pairs as $en => $fr) {
    if (!array_key_exists($en, $dict)) {
        $dict[$en] = $fr;
        $added++;
    }
}
if ($added > 0) {
    $export = "<?php\n\n/**\n * French translations. Keys are the English source strings used in the\n * views; anything missing here simply stays in English.\n */\nreturn [\n";
    foreach ($dict as $en => $fr) {
        $export .= '    ' . var_export($en, true) . ' => ' . var_export($fr, true) . ",\n";
    }
    $export .= "];\n";
    file_put_contents($dictFile, $export);
    echo "dictionary: $added entries added\n";
}
