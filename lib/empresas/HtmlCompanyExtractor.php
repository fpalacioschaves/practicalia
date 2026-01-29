<?php
// lib/empresas/HtmlCompanyExtractor.php
declare(strict_types=1);

final class HtmlCompanyExtractor
{
    public function extract(string $html, string $url): array
    {
        $data = [
            'nombre' => null,'email'=>null,'telefono'=>null,
            'direccion'=>null,'ciudad'=>null,'provincia'=>null,'codigo_postal'=>null,
            'web' => $url,
        ];
        libxml_use_internal_errors(true);
        $dom = new \DOMDocument(); $dom->loadHTML($html);
        $xp  = new \DOMXPath($dom);

        // schema.org
        $orgNodes = $xp->query('//*[@itemscope and (@itemtype="http://schema.org/Organization" or @itemtype="https://schema.org/Organization" or @itemtype="http://schema.org/LocalBusiness" or @itemtype="https://schema.org/LocalBusiness")]');
        if ($orgNodes && $orgNodes->length > 0) {
            $org = $orgNodes->item(0);
            $n = $xp->evaluate('.//*[@itemprop="name"]', $org);
            if ($n->length) $data['nombre'] = $this->clean($n->item(0)->textContent);
            foreach (['telephone'=>'telefono','email'=>'email'] as $prop=>$key) {
                $nodeList = $xp->evaluate('.//*[@itemprop="'.$prop.'"]', $org);
                if ($nodeList->length) $data[$key] = $this->clean($nodeList->item(0)->textContent);
            }
            $addr = $xp->evaluate('.//*[@itemprop="address" and (@itemtype="http://schema.org/PostalAddress" or @itemtype="https://schema.org/PostalAddress")]', $org);
            if ($addr->length) {
                $a = $addr->item(0);
                $data['direccion']     = $this->maybe($xp->evaluate('.//*[@itemprop="streetAddress"]', $a));
                $data['ciudad']        = $this->maybe($xp->evaluate('.//*[@itemprop="addressLocality"]', $a));
                $data['provincia']     = $this->maybe($xp->evaluate('.//*[@itemprop="addressRegion"]',   $a));
                $data['codigo_postal'] = $this->maybe($xp->evaluate('.//*[@itemprop="postalCode"]',      $a));
            }
        }

        // og:title / <title>
        if (!$data['nombre']) {
            $og = $xp->evaluate('//meta[@property="og:title"]/@content');
            if ($og->length) $data['nombre'] = $this->clean((string)$og->item(0)->nodeValue);
        }
        if (!$data['nombre']) {
            $titles = $dom->getElementsByTagName('title');
            if ($titles->length) $data['nombre'] = $this->clean((string)$titles->item(0)->textContent);
        }

        // Regex mínimos
        if (!$data['email'] && preg_match('/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/i', $html, $m)) $data['email'] = strtolower($m[0]);
        if (!$data['telefono'] && preg_match('/(\+34)?\s?(\d[\s\-]?){9,13}/', $html, $m)) $data['telefono'] = trim($m[0]);

        // Normaliza
        if ($data['telefono']) $data['telefono'] = preg_replace('/\s+/', '', $data['telefono']);
        if ($data['web'] && !preg_match('~^https?://~i', $data['web'])) $data['web'] = 'http://' . $data['web'];
        if ($data['nombre']) $data['nombre'] = mb_substr($data['nombre'], 0, 180);

        return $data;
    }

    private function clean(?string $s): ?string {
        if ($s === null) return null;
        return preg_replace('/\s+/', ' ', trim($s));
    }
    private function maybe(\DOMNodeList $nl): ?string {
        return $nl->length ? $this->clean($nl->item(0)->textContent) : null;
    }
}
