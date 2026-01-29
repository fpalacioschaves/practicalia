<?php
// config/empresa_sources.php
// Paquete base de "semillas" con listados de empresas/miembros.
// El crawler NO sale de estos dominios salvo para ir a la web corporativa de cada empresa encontrada.
// Si un selector deja de casar porque el sitio cambia su HTML, el crawler seguirá con el resto.

return [

  // =======================
  // ANDALUCÍA (por provincias)
  // =======================

  // Sevilla — Parque Tecnológico PCT Cartuja (directorio de empresas)
  [
    'name'          => 'PCT Cartuja (Sevilla) - Empresas',
    'url'           => 'https://www.pctcartuja.es/empresas/',
    'domain'        => 'www.pctcartuja.es',
    'item_selector' => 'a',           // el crawler filtrará externos / fichas
    'follow_company'=> true
  ],

  // Málaga — Málaga TechPark (PTA) — listado/empresas
  [
    'name'          => 'Málaga TechPark (PTA) - Empresas',
    'url'           => 'https://www.malagatechpark.com/empresas/',
    'domain'        => 'www.malagatechpark.com',
    'item_selector' => 'a',
    'follow_company'=> true
  ],

  // Almería — PITA Parque Científico-Tecnológico de Almería
  [
    'name'          => 'PITA Almería - Empresas',
    'url'           => 'https://pitalmeria.es/empresas/',
    'domain'        => 'pitalmeria.es',
    'item_selector' => 'a',
    'follow_company'=> true
  ],

  // Córdoba — Rabanales 21
  [
    'name'          => 'Rabanales 21 (Córdoba) - Empresas',
    'url'           => 'https://rabanales21.com/empresas/',
    'domain'        => 'rabanales21.com',
    'item_selector' => 'a',
    'follow_company'=> true
  ],

  // Cádiz — Zona Franca Cádiz / RED empresas (si la hubiera publicada)
  [
    'name'          => 'Zona Franca Cádiz - Empresas',
    'url'           => 'https://www.zonafrancacadiz.com/empresas/',
    'domain'        => 'www.zonafrancacadiz.com',
    'item_selector' => 'a',
    'follow_company'=> true
  ],

  // Granada — OnTech Innovation (clúster TIC andaluz)
  [
    'name'          => 'OnTech Innovation (Granada) - Asociados',
    'url'           => 'https://www.ontechinnovation.com/asociados/',
    'domain'        => 'www.ontechinnovation.com',
    'item_selector' => 'a',
    'follow_company'=> true
  ],

  // Andalucía — Andalucía Smart City (miembros)
  [
    'name'          => 'Andalucía Smart City - Miembros',
    'url'           => 'https://andaluciasmartcity.com/asociados/',
    'domain'        => 'andaluciasmartcity.com',
    'item_selector' => 'a',
    'follow_company'=> true
  ],

  // Huelva — Parque Huelva Empresarial (si publica directorio)
  [
    'name'          => 'Parque Huelva Empresarial - Empresas',
    'url'           => 'https://www.parquehuelvaempresarial.com/empresas/',
    'domain'        => 'www.parquehuelvaempresarial.com',
    'item_selector' => 'a',
    'follow_company'=> true
  ],

  // Jaén — Geolit Parque Científico-Tecnológico
  [
    'name'          => 'Geolit (Jaén) - Empresas',
    'url'           => 'https://www.geolit.es/empresas/',
    'domain'        => 'www.geolit.es',
    'item_selector' => 'a',
    'follow_company'=> true
  ],

  // Málaga — Polo de Contenidos Digitales (si lista residentes/empresas)
  [
    'name'          => 'Polo de Contenidos Digitales (Málaga) - Empresas',
    'url'           => 'https://www.polodigital.eu/empresas/',
    'domain'        => 'www.polodigital.eu',
    'item_selector' => 'a',
    'follow_company'=> true
  ],

  // Sevilla — Parque Aerópolis (aeroespacial) — empresas
  [
    'name'          => 'AERÓPOLIS (Sevilla) - Empresas',
    'url'           => 'https://www.aeropolis.es/empresas/',
    'domain'        => 'www.aeropolis.es',
    'item_selector' => 'a',
    'follow_company'=> true
  ],

  // Sevilla — PCT de la Salud (si existe directorio público)
  [
    'name'          => 'PCT Salud (Sevilla) - Empresas',
    'url'           => 'https://www.pctsalud.es/empresas/',
    'domain'        => 'www.pctsalud.es',
    'item_selector' => 'a',
    'follow_company'=> true
  ],

  // =======================
  // CÁMARAS / CLÚSTERES / ASOCIACIONES (ámbito andaluz o nacional)
  // =======================

  // Cámara de Comercio de Málaga — directorio (si público)
  [
    'name'          => 'Cámara de Comercio de Málaga - Directorio',
    'url'           => 'https://camaramalaga.com/directorio-empresas/',
    'domain'        => 'camaramalaga.com',
    'item_selector' => 'a',
    'follow_company'=> true
  ],

  // Cámara de Sevilla — directorio
  [
    'name'          => 'Cámara de Comercio de Sevilla - Directorio',
    'url'           => 'https://camaradesevilla.com/directorio-de-empresas/',
    'domain'        => 'camaradesevilla.com',
    'item_selector' => 'a',
    'follow_company'=> true
  ],

  // ETICOM / Clúster de Economía Digital Andalucía (si mantiene listado)
  [
    'name'          => 'Clúster Economía Digital Andalucía - Miembros',
    'url'           => 'https://www.economiadigitalandalucia.org/miembros/',
    'domain'        => 'www.economiadigitalandalucia.org',
    'item_selector' => 'a',
    'follow_company'=> true
  ],

  // AMETIC (nacional, TIC) — asociados
  [
    'name'          => 'AMETIC (España) - Asociados',
    'url'           => 'https://ametic.es/asociados',
    'domain'        => 'ametic.es',
    'item_selector' => 'a',
    'follow_company'=> true
  ],

  // Adigital (economía digital, nacional) — asociados
  [
    'name'          => 'Adigital (España) - Asociados',
    'url'           => 'https://www.adigital.org/asociados',
    'domain'        => 'www.adigital.org',
    'item_selector' => 'a',
    'follow_company'=> true
  ],

  // CONETIC (federación empresas TIC) — miembros
  [
    'name'          => 'CONETIC (España) - Miembros',
    'url'           => 'https://www.conetic.info/miembros/',
    'domain'        => 'www.conetic.info',
    'item_selector' => 'a',
    'follow_company'=> true
  ],

  // eAPyme (asociaciones TIC) — asociados
  [
    'name'          => 'eAPyme (España) - Asociados',
    'url'           => 'https://www.eapyme.es/asociados/',
    'domain'        => 'www.eapyme.es',
    'item_selector' => 'a',
    'follow_company'=> true
  ],

  // Clúster Audiovisual Andalucía (empresas del sector)
  [
    'name'          => 'Clúster Audiovisual de Andalucía - Empresas',
    'url'           => 'https://www.clusteraudiovisualdeandalucia.com/empresas/',
    'domain'        => 'www.clusteraudiovisualdeandalucia.com',
    'item_selector' => 'a',
    'follow_company'=> true
  ],

  // Secmotic/Smart City Cluster (nacional con fuerte presencia andaluza)
  [
    'name'          => 'Smart City Cluster (España) - Asociados',
    'url'           => 'https://www.smartcitycluster.org/asociados/',
    'domain'        => 'www.smartcitycluster.org',
    'item_selector' => 'a',
    'follow_company'=> true
  ],

  // =======================
  // FERIAS / PARQUES / OTRAS LISTAS (nacional con empresas tech)
  // =======================

  // South Tech Week / Digital Enterprise Show (si publica expositores)
  [
    'name'          => 'DES | South Tech Week - Expositores',
    'url'           => 'https://www.des-show.com/exhibitors/',
    'domain'        => 'www.des-show.com',
    'item_selector' => 'a',
    'follow_company'=> true
  ],

  // Alhambra Venture (startups) — portafolio / participantes
  [
    'name'          => 'Alhambra Venture - Startups',
    'url'           => 'https://alhambraventure.com/startups/',
    'domain'        => 'alhambraventure.com',
    'item_selector' => 'a',
    'follow_company'=> true
  ],

  // Transfiere Málaga (expositores)
  [
    'name'          => 'Transfiere Málaga - Expositores',
    'url'           => 'https://transfiere.es/expositores/',
    'domain'        => 'transfiere.es',
    'item_selector' => 'a',
    'follow_company'=> true
  ],

  // eShow (ecommerce/marketing) — expositores
  [
    'name'          => 'eShow - Expositores',
    'url'           => 'https://www.the-eshow.com/expositores/',
    'domain'        => 'www.the-eshow.com',
    'item_selector' => 'a',
    'follow_company'=> true
  ],

  // =======================
  // NOTAS
  // =======================
  // 1) Este paquete es un punto de partida. Si alguna URL cambia o una página no publica el listado, el crawler la saltará.
  // 2) Para “clavar” una fuente, pon aquí su CSS concreto (por ejemplo: '.company-card a.company-link').
  // 3) Si una fuente sólo enlaza a fichas internas, 'follow_company' permite que el crawler abra la ficha y busque la web externa corporativa.
];
