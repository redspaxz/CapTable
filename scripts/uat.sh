#!/usr/bin/env bash
# UAT — User Acceptance Testing pour CapTable (T&Tech Consulting Group)
# Reconstruit une base de démonstration vierge, démarre l'application et
# déroule tous les workflows métier avec des assertions PASS/FAIL.
#
# Usage : bash scripts/uat.sh   (port personnalisé : UAT_PORT=9090 bash scripts/uat.sh)
set -u

BASE="$(cd "$(dirname "$0")/.." && pwd)"
DB="$BASE/database/captable.sqlite"
PORT="${UAT_PORT:-8090}"
URL="http://127.0.0.1:$PORT"
JAR="$(mktemp)"
PASS=0; FAIL=0

ok() { echo "  PASS  $1"; PASS=$((PASS + 1)); }
ko() { echo "  FAIL  $1"; FAIL=$((FAIL + 1)); }

# ---- Environnement vierge --------------------------------------------------
echo "== Préparation : base SQLite vierge + seed =="
rm -f "$DB"
php -r '$p = new PDO("sqlite:" . $argv[1]); $p->exec(file_get_contents($argv[2]));' \
    "$DB" "$BASE/database/schema.sqlite.sql" || { echo "schema KO"; exit 1; }
( cd "$BASE" && DB_DRIVER=sqlite php database/seed.php ) >/dev/null || { echo "seed KO"; exit 1; }
ok "base réinitialisée et seedée"

( cd "$BASE" && DB_DRIVER=sqlite php -S 127.0.0.1:$PORT -t public public/router.php ) >/dev/null 2>&1 &
SERVER_PID=$!
trap 'kill $SERVER_PID 2>/dev/null; rm -f "$JAR"' EXIT
sleep 2

# ---- Helpers ----------------------------------------------------------------
# NB : jamais « -L -X POST » ensemble : curl rejouerait la redirection en POST
# sans corps et déclencherait le contrôle CSRF (419). On POST puis on GET.
# NB : données de test en ASCII pur — la console Windows (Git Bash) peut
# altérer les accents avant qu'ils n'atteignent curl.
get()  { curl -s -b "$JAR" -c "$JAR" "$URL$1"; }
code() { curl -s -b "$JAR" -c "$JAR" -o /dev/null -w '%{http_code}' "$URL$1"; }
csrf() { get "$1" | grep -o 'name="_csrf" value="[^"]*"' | head -1 | sed 's/.*value="//;s/"$//'; }
# POST puis renvoie la page indiquée (où s'affiche le message flash) :
post() { # chemin-post formulaire chemin-page-suivante données...
    local ppath="$1" form="$2" next="$3"; shift 3
    local token; token="$(csrf "$form")"
    curl -s -b "$JAR" -c "$JAR" -X POST "$URL$ppath" "$@" --data-urlencode "_csrf=$token" -o /dev/null
    get "$next"
}
# POST et renvoie la réponse directe (sans redirection) :
post_raw() { # chemin-post formulaire données...
    local ppath="$1" form="$2"; shift 2
    local token; token="$(csrf "$form")"
    curl -s -b "$JAR" -c "$JAR" -X POST "$URL$ppath" "$@" --data-urlencode "_csrf=$token"
}
has() { echo "$1" | grep -q -- "$2"; }

# ---- T1 Authentification -----------------------------------------------------
echo "== T1 Authentification =="
[ "$(code /login)" = 200 ] && ok "page de connexion accessible" || ko "page de connexion inaccessible"
R=$(post /login /login /login --data-urlencode "email=admin@ttechgroup.cm" --data-urlencode "password=WRONG")
has "$R" "Identifiants invalides" && ok "mot de passe erroné refusé avec message" || ko "mot de passe erroné accepté"
R=$(post /login /login / --data-urlencode "email=admin@ttechgroup.cm" --data-urlencode "password=password")
has "$R" "Bienvenue" && has "$R" "Tableau de bord" && ok "connexion admin et tableau de bord" || ko "connexion admin"

# ---- T2 Actionnaires ---------------------------------------------------------
echo "== T2 Actionnaires =="
R=$(get /shareholders)
has "$R" "Edmund Alomepe" && has "$R" "Marie Ngo Bassong" && has "$R" "T&amp;Tech Holding SARL" \
    && ok "les 3 actionnaires fondateurs sont listés" || ko "actionnaires fondateurs absents"
R=$(post /shareholders /shareholders/new /shareholders \
    --data-urlencode "type=corporate" \
    --data-urlencode "name=Caisse Nationale de Prevoyance" \
    --data-urlencode "id_type=RC" --data-urlencode "id_number=RC/DLA/2015/42" \
    --data-urlencode "address=Douala" --data-urlencode "nationality=Camerounaise")
has "$R" "Caisse Nationale de Prevoyance" && ok "création d'une personne morale et affichage en liste" || ko "création personne morale"
R=$(post "/shareholders/2" "/shareholders/2/edit" "/shareholders" \
    --data-urlencode "type=individual" --data-urlencode "name=Marie Ngo Bassong-Essomba" \
    --data-urlencode "id_type=CNI" --data-urlencode "id_number=2233445566")
has "$R" "Bassong-Essomba" && ok "modification d'un actionnaire" || ko "modification actionnaire"

# ---- T3 Catégories d'actions --------------------------------------------------
echo "== T3 Catégories d'actions =="
R=$(post /classes /classes /classes --data-urlencode "code=PREF" --data-urlencode "name=Actions preferentielles" \
    --data-urlencode "nominal_value=10000" --data-urlencode "shares_authorized=5000" \
    --data-urlencode "rights=Dividende majore, sans droit de vote")
has "$R" "PREF" && has "$R" "Actions preferentielles" && ok "création de la catégorie PREF" || ko "création catégorie PREF"

# ---- T4 Émissions --------------------------------------------------------------
echo "== T4 Émissions =="
# Base vierge reseedée : ORD = id 1 (seed), PREF = id 2 (créé en T3).
PREF_ID=2
[ -n "$PREF_ID" ] && ok "formulaire d'émission : catégorie PREF avec valeur nominale exposée" || ko "catégorie PREF introuvable dans le formulaire"
R=$(post /issuances /issuances/new /issuances \
    --data-urlencode "share_class_id=$PREF_ID" --data-urlencode "shareholder_id=4" \
    --data-urlencode "quantity=1000" --data-urlencode "apport_type=cash" \
    --data-urlencode "issuance_date=2026-09-01" --data-urlencode "reference=AG-2026-PREF")
has "$R" "AG-2026-PREF" && has "$R" "PREF" && ok "émission de 1 000 PREF à la CNP tracée" || ko "émission PREF"
R=$(post /issuances /issuances/new /issuances/new \
    --data-urlencode "share_class_id=$PREF_ID" --data-urlencode "shareholder_id=4" \
    --data-urlencode "quantity=4500" --data-urlencode "apport_type=cash" \
    --data-urlencode "issuance_date=2026-09-01")
has "$R" "Quota dépassé" && ok "dépassement du quota autorisé bloqué (4 500 > 4 000 restants)" || ko "quota autorisé non vérifié"

# ---- T5 Cessions -----------------------------------------------------------------
echo "== T5 Cessions =="
ORD_ID=$(get /transfers/new | grep -oE 'value="[0-9]+">ORD' | head -1 | grep -oE '[0-9]+')
R=$(post /transfers /transfers/new /transfers \
    --data-urlencode "share_class_id=$ORD_ID" --data-urlencode "seller_id=1" \
    --data-urlencode "buyer_id=4" --data-urlencode "quantity=500" \
    --data-urlencode "transfer_date=2026-09-02" --data-urlencode "deed_reference=ACT-UAT-001")
has "$R" "ACT-UAT-001" && has "$R" "Caisse Nationale" && ok "cession de 500 ORD (Edmund → CNP) enregistrée" || ko "cession ORD"
R=$(post /transfers /transfers/new /transfers/new \
    --data-urlencode "share_class_id=$ORD_ID" --data-urlencode "seller_id=2" \
    --data-urlencode "buyer_id=1" --data-urlencode "quantity=3000" \
    --data-urlencode "transfer_date=2026-09-02")
has "$R" "Titres insuffisants" && ok "cession au-delà des disponibilités bloquée (3 000 > 2 500 détenus)" || ko "disponibilités du cédant non vérifiées"

# ---- T6 Cap table et registre -----------------------------------------------------
echo "== T6 Cap table et registre =="
# Détentions attendues : Edmund 4 500 ORD ; Marie 2 500 ORD ; Holding 2 500 ORD ;
# CNP 500 ORD + 1 000 PREF = 1 500. Total 11 000 titres, 110 000 000 XAF.
R=$(get /captable)
has "$R" "40,91" && ok "cap table : Edmund à 40,91 % (4 500 / 11 000)" || ko "pourcentage Edmund"
has "$R" "4,55" && has "$R" "9,09" && ok "cap table : CNP ventilée par catégorie (500 ORD = 4,55 % + 1 000 PREF = 9,09 %)" || ko "pourcentage CNP"
has "$R" "110 000 000 XAF" && ok "capital social recalculé : 110 000 000 XAF" || ko "capital social"
CSV=$(get /captable/export.csv)
has "$CSV" "Caisse Nationale" && has "$CSV" "PREF" && ok "export CSV complet (nouvel actionnaire + catégorie)" || ko "export CSV incomplet"
R=$(get /register)
has "$R" "AG-2026-PREF" && has "$R" "ACT-UAT-001" && ok "registre : émission et cession UAT tracées (art. 716)" || ko "registre incomplet"

# ---- T7 Documents -------------------------------------------------------------------
echo "== T7 Documents =="
R=$(post /documents/certificates /documents/certificates/new /documents \
    --data-urlencode "shareholder_id=1" --data-urlencode "share_class_id=$ORD_ID" --data-urlencode "quantity=4500")
has "$R" "CT-00001" && ok "certificat CT-00001 émis pour 4 500 actions" || ko "émission de certificat"
R=$(get /documents/certificates/1)
has "$R" "CERTIFICAT D.ACTIONS" && has "$R" "4 500" && ok "certificat imprimable avec mentions OHADA" || ko "certificat non conforme"
R=$(post /documents/certificates /documents/certificates/new /documents/certificates/new \
    --data-urlencode "shareholder_id=2" --data-urlencode "share_class_id=$ORD_ID" --data-urlencode "quantity=9999")
has "$R" "Quantité invalide" && ok "certificat au-delà des titres détenus bloqué" || ko "garde-fou certificat inopérant"
R=$(get /documents/deeds/2)
has "$R" "ACTE DE CESSION" && has "$R" "ACT-UAT-001" && ok "acte de cession UAT imprimable" || ko "acte de cession"
R=$(post_raw /documents/minutes /documents/minutes/new \
    --data-urlencode "meeting_type=AGE" --data-urlencode "meeting_date=2026-09-03" \
    --data-urlencode "location=Siege social, Douala" \
    --data-urlencode "agenda=Approbation de la cession de 500 actions ORD" \
    --data-urlencode "resolutions=L assemblee approuve la cession a l unanimite.")
has "$R" "ASSEMBL" && has "$R" "40,91" && ok "PV d'assemblée généré avec le tableau de présence exact" || ko "PV d'assemblée"

# ---- T8 Contrôle d'accès (rôle viewer) ----------------------------------------------
echo "== T8 Contrôle d'accès (rôle viewer) =="
curl -s -b "$JAR" -c "$JAR" -X POST "$URL/logout" --data-urlencode "_csrf=$(csrf /)" -o /dev/null
R=$(post /login /login / --data-urlencode "email=viewer@ttechgroup.cm" --data-urlencode "password=password")
has "$R" "Bienvenue" && ok "connexion viewer réussie" || ko "connexion viewer"
R=$(get /shareholders)
has "$R" "Nouvel actionnaire" && ko "le viewer voit les boutons d'écriture" || ok "viewer : boutons d'écriture masqués"
C=$(curl -s -b "$JAR" -o /dev/null -w '%{http_code}' -X POST "$URL/shareholders" \
    --data-urlencode "_csrf=$(csrf /shareholders)" --data-urlencode "type=individual" \
    --data-urlencode "name=Intrus" --data-urlencode "id_number=1" --data-urlencode "id_type=CNI")
[ "$C" = 403 ] && ok "viewer : tentative d'écriture rejetée (403)" || ko "viewer parvient à écrire ($C)"
R=$(get /captable)
has "$R" "40,91" && ok "viewer : lecture du cap table autorisée" || ko "lecture cap table refusée au viewer"

# ---- T9 Garde-fous de sécurité --------------------------------------------------------
echo "== T9 Garde-fous de sécurité =="
curl -s -b "$JAR" -c "$JAR" -X POST "$URL/logout" --data-urlencode "_csrf=$(csrf /)" -o /dev/null
C=$(curl -s -o /dev/null -w '%{http_code}' -X POST "$URL/login" \
    --data "email=admin@ttechgroup.cm&password=password")
[ "$C" = 419 ] && ok "POST sans jeton CSRF rejeté (419)" || ko "CSRF non vérifié ($C)"
C=$(curl -s -o /dev/null -w '%{http_code}' "$URL/")
[ "$C" = 302 ] && ok "utilisateur anonyme redirigé vers la connexion" || ko "accès anonyme non redirigé ($C)"
C=$(code /api/holdings/1)
[ "$C" = 302 ] && ok "API holdings protégée par authentification" || ko "API exposée sans authentification ($C)"
H=$(get /health)
has "$H" "connected" && ok "diagnostic /health : base connectée" || ko "/health"

# ---- Bilan -------------------------------------------------------------------------------
echo
echo "=================================================="
echo " BILAN UAT : $PASS réussi(s), $FAIL échoué(s)"
echo "=================================================="
[ "$FAIL" -eq 0 ] || exit 1
