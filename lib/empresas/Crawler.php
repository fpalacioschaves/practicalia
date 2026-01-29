namespace App\Empresas;

use PDO;
use RuntimeException;
use Throwable;
use DOMDocument;
use DOMXPath;
use DOMElement;
use InvalidArgumentException;


final class Crawler
{
private array $sources;
private \PDO $pdo;
private HtmlCompanyExtractor $extractor;

public function __construct(array $sources, \PDO $pdo)
{
$this->sources = $sources;
$this->pdo = $pdo;
$this->extractor = new HtmlCompanyExtractor();
$this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
$this->pdo->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
}

/**
* Ejecuta el rastreo automático:
* - Entra en cada fuente semilla
* - Detecta enlaces a posibles empresas (externos o fichas)
* - Llega a la web final de la empresa
* - Extrae nombre/email/teléfono/dirección
* - Deduplica e inserta/actualiza en `empresas`
*/
public function run(array $opts): array
{
$sector = trim((string)($opts['sector'] ?? ''));
$provincia = trim((string)($opts['provincia'] ?? ''));
$ciudad = trim((string)($opts['ciudad'] ?? ''));
$keywords = array_filter(array_map('trim', (array)($opts['keywords'] ?? [])));
$limitPer = max(1, min(500, (int)($opts['limit_per_source'] ?? 100)));
$dryRun = (bool)($opts['dry_run'] ?? false);

if ($sector === '' || $provincia === '') {
throw new \InvalidArgumentException('sector y provincia son obligatorios');
}

$summary = [];
foreach ($this->sources as $src) {
$name = $src['name'] ?? $src['url'];
$url = $src['url'];
$domain = $src['domain'] ?? parse_url($url, PHP_URL_HOST);
$sel = $src['item_selector'] ?? 'a';
$follow = (bool)($src['follow_company'] ?? true);

$html = $this->fetch($url);
$companyLinks = $this->extractCompanyLinks($html, $url, $domain, $sel, $keywords);

$found = 0; $inserted = 0; $updated = 0; $skipped = 0;

foreach ($companyLinks as $link) {
if ($found >= $limitPer) break;
$found++;

// Si es ficha interna, intenta descubrir la web final (buscando enlaces externos)
$companyUrl = $link;
if ($follow && $this->sameHost($companyUrl, $domain)) {
$companyUrl = $this->findExternalWebsite($companyUrl, $domain) ?? $companyUrl;
}

// Si sigue siendo del mismo dominio (directorio), saltamos
if ($this->sameHost($companyUrl, $domain)) { $skipped++; continue; }

// Carga la web de la empresa y extrae
$ch = $this->fetchSafe($companyUrl);
if ($ch === null) { $skipped++; continue; }

$info = $this->extractor->extract($ch, $companyUrl);
// Filtros por keywords en nombre (si se pidieron)
if ($keywords && !$this->matchKeywords($info, $keywords)) { $skipped++; continue; }

// Completa criterios del profesorado
$info['sector'] = $sector;
$info['provincia'] = $provincia;
if ($ciudad && empty($info['ciudad'])) $info['ciudad'] = $ciudad;

if ($dryRun) {
$updated++; // contamos como “preview”
continue;
}

// Dedup: por web o (nombre+ciudad)
$id = $this->findExisting($info['web'], $info['nombre'], $info['ciudad'] ?? null);
if ($id) {
$this->updateCompany($id, $info);
$updated++;
} else {
$this->insertCompany($info);
$inserted++;
}

// breve pausa para no ser agresivos
usleep(150000); // 0.15s
}

$summary[] = compact('name', 'url', 'found', 'inserted', 'updated', 'skipped');
// pausa entre fuentes
usleep(300000);
}

return ['ok'=>true,'sources'=>$summary,'dry_run'=>$dryRun];
}

// ---------------- Helpers ----------------

private function fetch(string $url): string {
$ctx = stream_context_create(['http' => [
'method' => 'GET',
'header' => "User-Agent: PracticaliaCrawler/1.0\r\n"
]]);
$html = @file_get_contents($url, false, $ctx);
if ($html === false) throw new \RuntimeException("No se pudo leer: $url");
return $html;
}

private function fetchSafe(string $url): ?string {
try { return $this->fetch($url); } catch (\Throwable $e) { return null; }
}

private function extractCompanyLinks(string $html, string $baseUrl, string $domain, string $itemSelector, array
$keywords): array
{
libxml_use_internal_errors(true);
$dom = new \DOMDocument(); $dom->loadHTML($html);
$xp = new \DOMXPath($dom);

// Selector CSS -> XPath mínimo
$xpath = $this->cssToXpath($itemSelector);
$nodes = $xp->query($xpath);

$links = [];
if ($nodes) {
foreach ($nodes as $n) {
if (!($n instanceof \DOMElement)) continue;
$href = $n->getAttribute('href') ?: '';
if ($href === '' || str_starts_with($href,'mailto:') || str_starts_with($href,'tel:')) continue;
$url = $this->absolutizeUrl($href, $baseUrl);

// descartamos enlaces basura o redes sociales (para priorizar webs corporativas)
if ($this->isNoise($url)) continue;

// Acepta:
// a) enlaces EXTERNOS al dominio semilla (probable web de empresa), o
// b) enlaces internos que apunten a fichas (los seguiremos luego)
if (!$this->sameHost($url, $domain) || $this->looksLikeProfile($url)) {
$links[] = $url;
}
}
}

// Unicidad y límite rápido
$links = array_values(array_unique($links));

// Si hay keywords, prioriza enlaces que las contengan en URL o texto
if ($keywords) {
$links = $this->prioritizeByKeywords($links, $keywords);
}

return $links;
}

private function findExternalWebsite(string $fichaUrl, string $seedDomain): ?string
{
$html = $this->fetchSafe($fichaUrl);
if ($html === null) return null;

libxml_use_internal_errors(true);
$dom = new \DOMDocument(); $dom->loadHTML($html);
$xp = new \DOMXPath($dom);
$a = $xp->query('//a[@href]');
if (!$a || $a->length === 0) return null;

foreach ($a as $el) {
/** @var \DOMElement $el */
$href = $el->getAttribute('href');
if ($href === '' || str_starts_with($href,'mailto:') || str_starts_with($href,'tel:')) continue;
$url = $this->absolutizeUrl($href, $fichaUrl);
if ($this->isNoise($url)) continue;
if (!$this->sameHost($url, $seedDomain)) return $url; // ¡web externa encontrada!
}
return null;
}

private function findExisting(?string $web, ?string $nombre, ?string $ciudad): ?int
{
$sel = $this->pdo->prepare("SELECT id FROM empresas WHERE (web = :web AND web <> '') OR (nombre = :nombre AND ciudad <=>
        :ciudad) LIMIT 1");
        $sel->execute([':web'=>$web, ':nombre'=>$nombre, ':ciudad'=>$ciudad]);
        $id = $sel->fetchColumn();
        return $id ? (int)$id : null;
        }

        private function updateCompany(int $id, array $e): void
        {
        $upd = $this->pdo->prepare("
        UPDATE empresas SET
        nombre=COALESCE(:nombre,nombre),
        sector=COALESCE(:sector,sector),
        provincia=COALESCE(:provincia,provincia),
        ciudad=COALESCE(:ciudad,ciudad),
        direccion=COALESCE(:direccion,direccion),
        codigo_postal=COALESCE(:cp,codigo_postal),
        web=COALESCE(:web,web),
        email=COALESCE(:email,email),
        telefono=COALESCE(:telefono,telefono),
        activo=1
        WHERE id=:id
        ");
        $upd->execute([
        ':nombre'=>$e['nombre']??null, ':sector'=>$e['sector']??null, ':provincia'=>$e['provincia']??null,
        ':ciudad'=>$e['ciudad']??null, ':direccion'=>$e['direccion']??null, ':cp'=>$e['codigo_postal']??null,
        ':web'=>$e['web']??null, ':email'=>$e['email']??null, ':telefono'=>$e['telefono']??null, ':id'=>$id
        ]);
        }

        private function insertCompany(array $e): void
        {
        $ins = $this->pdo->prepare("
        INSERT INTO empresas (nombre, sector, provincia, ciudad, direccion, codigo_postal, web, email, telefono, activo)
        VALUES (:nombre,:sector,:provincia,:ciudad,:direccion,:cp,:web,:email,:telefono,1)
        ");
        $ins->execute([
        ':nombre'=>$e['nombre'], ':sector'=>$e['sector'], ':provincia'=>$e['provincia']??null,
        ':ciudad'=>$e['ciudad']??null, ':direccion'=>$e['direccion']??null, ':cp'=>$e['codigo_postal']??null,
        ':web'=>$e['web'], ':email'=>$e['email']??null, ':telefono'=>$e['telefono']??null
        ]);
        }

        private function matchKeywords(array $info, array $kw): bool
        {
        $hay = mb_strtolower(implode(' ', array_filter([$info['nombre']??'', $info['direccion']??'',
        $info['web']??''])));
        foreach ($kw as $k) {
        if (mb_stripos($hay, mb_strtolower($k)) !== false) return true;
        }
        return false;
        }

        private function prioritizeByKeywords(array $links, array $kw): array
        {
        usort($links, function($a,$b) use($kw){
        $sa = $this->scoreUrl($a, $kw);
        $sb = $this->scoreUrl($b, $kw);
        return $sb <=> $sa;
            });
            return $links;
            }
            private function scoreUrl(string $u, array $kw): int {
            $s = 0; $lu = mb_strtolower($u);
            foreach ($kw as $k) if (str_contains($lu, mb_strtolower($k))) $s++;
            return $s;
            }

            private function isNoise(string $url): bool
            {
            $host = parse_url($url, PHP_URL_HOST) ?: '';
            $ban =
            ['facebook.com','twitter.com','x.com','instagram.com','linkedin.com','youtube.com','youtu.be','wa.me','t.me'];
            foreach ($ban as $b) if (str_ends_with($host, $b)) return true;
            if (preg_match('~/wp\-content/|#|/page/|/pagin|/feed$|\.pdf$|\.jpg$|\.png$|\.gif$~i', $url)) return true;
            return false;
            }

            private function sameHost(string $url, string $host): bool
            {
            $h = parse_url($url, PHP_URL_HOST) ?: '';
            return $h === $host;
            }

            private function absolutizeUrl(string $href, string $base): string
            {
            if (preg_match('~^https?://~i', $href)) return $href;
            $p = parse_url($base);
            if (!$p || empty($p['scheme']) || empty($p['host'])) return $href;
            $prefix = $p['scheme'].'://'.$p['host'].(isset($p['port'])?':'.$p['port']:'');
            if (str_starts_with($href,'/')) return $prefix.$href;
            $dir = rtrim(dirname($p['path'] ?? '/'), '/');
            return $prefix.$dir.'/'.$href;
            }

            private function cssToXpath(string $css): string
            {
            $css = trim($css);
            if ($css === '' || $css === '*') return '//*';
            $parts = preg_split('/\s+/', $css);
            $xpath = '';
            foreach ($parts as $p) {
            $seg='//';
            if ($p[0]==='#') { $seg.="*[@id='".substr($p,1)."']"; }
            elseif ($p[0]==='.') { $c=substr($p,1); $seg.="*[contains(concat(' ', normalize-space(@class), ' '), ' {$c}
            ')]"; }
            else { $seg.=$p; }
            $xpath.=$seg;
            }
            return $xpath;
            }
            }