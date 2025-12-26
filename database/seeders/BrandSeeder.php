<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;
use Lunar\Models\Brand;
use Lunar\FieldTypes\Text;
use Lunar\FieldTypes\TranslatedText;

/**
 * Seeder for creating sample brands with logos and descriptions.
 */
class BrandSeeder extends Seeder
{
    /**
     * Generate a simple PNG logo for a brand (colored background + initials).
     *
     * Returns a temporary file path.
     */
    protected function generateBrandLogoPng(string $brandName, int $size = 400): string
    {
        if (!function_exists('imagecreatetruecolor')) {
            throw new \RuntimeException('GD extension is required to generate PNG logos.');
        }

        $img = imagecreatetruecolor($size, $size);
        if (!$img) {
            throw new \RuntimeException('Failed to create image canvas.');
        }

        // Deterministic background color based on brand name.
        $hash = crc32(mb_strtolower($brandName));
        $r = 80 + ($hash & 0x7F);
        $g = 80 + (($hash >> 8) & 0x7F);
        $b = 80 + (($hash >> 16) & 0x7F);

        $bg = imagecolorallocate($img, $r, $g, $b);
        $fg = imagecolorallocate($img, 255, 255, 255);

        imagefilledrectangle($img, 0, 0, $size, $size, $bg);

        $initials = mb_strtoupper(
            collect(preg_split('/\s+/', trim($brandName)) ?: [])
                ->filter()
                ->map(fn ($part) => mb_substr($part, 0, 1))
                ->implode('')
        );

        if ($initials === '') {
            $initials = mb_strtoupper(mb_substr($brandName, 0, 2));
        }
        $initials = mb_substr($initials, 0, 3);

        // Use built-in GD font to avoid bundling external font files.
        $font = 5; // Largest built-in font
        $textW = imagefontwidth($font) * strlen($initials);
        $textH = imagefontheight($font);
        $x = (int) (($size - $textW) / 2);
        $y = (int) (($size - $textH) / 2);

        // Slight shadow for contrast
        $shadow = imagecolorallocate($img, 0, 0, 0);
        imagestring($img, $font, $x + 2, $y + 2, $initials, $shadow);
        imagestring($img, $font, $x, $y, $initials, $fg);

        $tmp = tempnam(sys_get_temp_dir(), 'brand_logo_');
        if ($tmp === false) {
            imagedestroy($img);
            throw new \RuntimeException('Failed to create temporary file.');
        }

        // Ensure png extension (helps with mime detection / file naming)
        $pngPath = $tmp . '.png';
        @unlink($pngPath);

        $ok = imagepng($img, $pngPath, 9);
        imagedestroy($img);
        @unlink($tmp);

        if (!$ok) {
            throw new \RuntimeException('Failed to write PNG logo.');
        }

        return $pngPath;
    }

    /**
     * Ensure a brand has a logo in the `logo` media collection.
     */
    protected function ensureBrandLogo(Brand $brand): void
    {
        try {
            if ($brand->getFirstMedia('logo')) {
                return;
            }

            $pngPath = $this->generateBrandLogoPng($brand->name, 400);

            $filename = str($brand->name)->slug()->append('-logo.png')->toString();

            $brand->addMedia($pngPath)
                ->usingName($brand->name . ' Logo')
                ->usingFileName($filename)
                ->toMediaCollection('logo');

            @unlink($pngPath);
        } catch (\Throwable $e) {
            // Seeding should not hard-fail if image generation fails in some environments.
            Log::warning('BrandSeeder: failed to attach logo', [
                'brand_id' => $brand->id ?? null,
                'brand_name' => $brand->name ?? null,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🏷️ Creating brands...');

        $brands = [
            // Technology & Electronics
            [
                'name' => 'Apple',
                'description' => [
                    'en' => 'Apple Inc. is an American multinational technology company that designs, develops, and sells consumer electronics, computer software, and online services.',
                    'es' => 'Apple Inc. es una empresa multinacional de tecnología estadounidense que diseña, desarrolla y vende productos electrónicos de consumo, software informático y servicios en línea.',
                    'fr' => 'Apple Inc. est une entreprise technologique multinationale américaine qui conçoit, développe et vend des produits électroniques grand public, des logiciels informatiques et des services en ligne.',
                ],
                'website_url' => 'https://www.apple.com',
            ],
            [
                'name' => 'Samsung',
                'description' => [
                    'en' => 'Samsung is a South Korean multinational manufacturing conglomerate headquartered in Samsung Town, Seoul.',
                    'es' => 'Samsung es un conglomerado manufacturero multinacional surcoreano con sede en Samsung Town, Seúl.',
                    'fr' => 'Samsung est un conglomérat manufacturier multinational sud-coréen basé à Samsung Town, Séoul.',
                ],
                'website_url' => 'https://www.samsung.com',
            ],
            [
                'name' => 'Sony',
                'description' => [
                    'en' => 'Sony Corporation is a Japanese multinational conglomerate corporation headquartered in Kōnan, Minato, Tokyo.',
                    'es' => 'Sony Corporation es una corporación multinacional japonesa con sede en Kōnan, Minato, Tokio.',
                    'fr' => 'Sony Corporation est une société multinationale japonaise basée à Kōnan, Minato, Tokyo.',
                ],
                'website_url' => 'https://www.sony.com',
            ],
            [
                'name' => 'Microsoft',
                'description' => [
                    'en' => 'Microsoft Corporation is an American multinational technology corporation which produces computer software, consumer electronics, personal computers, and related services.',
                    'es' => 'Microsoft Corporation es una corporación tecnológica multinacional estadounidense que produce software informático, productos electrónicos de consumo, computadoras personales y servicios relacionados.',
                    'fr' => 'Microsoft Corporation est une société technologique multinationale américaine qui produit des logiciels informatiques, des produits électroniques grand public, des ordinateurs personnels et des services connexes.',
                ],
                'website_url' => 'https://www.microsoft.com',
            ],
            [
                'name' => 'Dell',
                'description' => [
                    'en' => 'Dell Technologies Inc. is an American multinational technology company that develops, sells, repairs, and supports computers and related products and services.',
                    'es' => 'Dell Technologies Inc. es una empresa tecnológica multinacional estadounidense que desarrolla, vende, repara y da soporte a computadoras y productos y servicios relacionados.',
                    'fr' => 'Dell Technologies Inc. est une société technologique multinationale américaine qui développe, vend, répare et prend en charge des ordinateurs et des produits et services connexes.',
                ],
                'website_url' => 'https://www.dell.com',
            ],
            [
                'name' => 'HP',
                'description' => [
                    'en' => 'HP Inc. is an American multinational information technology company that develops and provides a wide variety of hardware components, as well as software and related services.',
                    'es' => 'HP Inc. es una empresa multinacional estadounidense de tecnología de la información que desarrolla y proporciona una amplia variedad de componentes de hardware, así como software y servicios relacionados.',
                    'fr' => 'HP Inc. est une société multinationale américaine de technologies de l\'information qui développe et fournit une large gamme de composants matériels, ainsi que des logiciels et services connexes.',
                ],
                'website_url' => 'https://www.hp.com',
            ],
            [
                'name' => 'Canon',
                'description' => [
                    'en' => 'Canon Inc. is a Japanese multinational corporation specialized in the manufacture of imaging and optical products, including cameras, camcorders, photocopiers, steppers, computer printers and medical equipment.',
                    'es' => 'Canon Inc. es una corporación multinacional japonesa especializada en la fabricación de productos de imagen y óptica, incluyendo cámaras, videocámaras, fotocopiadoras, steppers, impresoras de computadora y equipos médicos.',
                    'fr' => 'Canon Inc. est une société multinationale japonaise spécialisée dans la fabrication de produits d\'imagerie et optiques, notamment des appareils photo, des caméscopes, des photocopieuses, des steppers, des imprimantes informatiques et des équipements médicaux.',
                ],
                'website_url' => 'https://www.canon.com',
            ],
            [
                'name' => 'LG',
                'description' => [
                    'en' => 'LG Corporation is a South Korean multinational conglomerate corporation founded in 1947.',
                    'es' => 'LG Corporation es una corporación multinacional surcoreana fundada en 1947.',
                    'fr' => 'LG Corporation est une société multinationale sud-coréenne fondée en 1947.',
                ],
                'website_url' => 'https://www.lg.com',
            ],
            [
                'name' => 'Lenovo',
                'description' => [
                    'en' => 'Lenovo Group Limited is a Chinese multinational technology company that designs, develops, manufactures, and sells personal computers, tablet computers, smartphones, workstations, servers, electronic storage devices, IT management software, and smart televisions.',
                    'es' => 'Lenovo Group Limited es una empresa tecnológica multinacional china que diseña, desarrolla, fabrica y vende computadoras personales, tabletas, smartphones, estaciones de trabajo, servidores, dispositivos de almacenamiento electrónico, software de gestión de TI y televisores inteligentes.',
                    'fr' => 'Lenovo Group Limited est une société technologique multinationale chinoise qui conçoit, développe, fabrique et vend des ordinateurs personnels, des tablettes, des smartphones, des stations de travail, des serveurs, des dispositifs de stockage électroniques, des logiciels de gestion informatique et des téléviseurs intelligents.',
                ],
                'website_url' => 'https://www.lenovo.com',
            ],
            [
                'name' => 'Asus',
                'description' => [
                    'en' => 'ASUS is a Taiwanese multinational computer and phone hardware and electronics company headquartered in Beitou District, Taipei, Taiwan.',
                    'es' => 'ASUS es una empresa multinacional taiwanesa de hardware informático y telefónico y productos electrónicos con sede en el distrito de Beitou, Taipei, Taiwán.',
                    'fr' => 'ASUS est une société multinationale taïwanaise de matériel informatique et téléphonique et d\'électronique basée dans le district de Beitou, Taipei, Taïwan.',
                ],
                'website_url' => 'https://www.asus.com',
            ],
            [
                'name' => 'Bose',
                'description' => [
                    'en' => 'Bose Corporation is an American manufacturing company that predominantly sells audio equipment.',
                    'es' => 'Bose Corporation es una empresa manufacturera estadounidense que principalmente vende equipos de audio.',
                    'fr' => 'Bose Corporation est une société de fabrication américaine qui vend principalement des équipements audio.',
                ],
                'website_url' => 'https://www.bose.com',
            ],
            [
                'name' => 'JBL',
                'description' => [
                    'en' => 'JBL is an American audio equipment manufacturer owned by Harman International Industries, a subsidiary of Samsung Electronics.',
                    'es' => 'JBL es un fabricante estadounidense de equipos de audio propiedad de Harman International Industries, una subsidiaria de Samsung Electronics.',
                    'fr' => 'JBL est un fabricant américain d\'équipements audio appartenant à Harman International Industries, une filiale de Samsung Electronics.',
                ],
                'website_url' => 'https://www.jbl.com',
            ],
            // Fashion & Apparel
            [
                'name' => 'Nike',
                'description' => [
                    'en' => 'Nike, Inc. is an American multinational corporation that is engaged in the design, development, manufacturing, and worldwide marketing and sales of footwear, apparel, equipment, accessories, and services.',
                    'es' => 'Nike, Inc. es una corporación multinacional estadounidense que se dedica al diseño, desarrollo, fabricación y comercialización y venta mundial de calzado, ropa, equipamiento, accesorios y servicios.',
                    'fr' => 'Nike, Inc. est une société multinationale américaine qui conçoit, développe, fabrique et commercialise des chaussures, des vêtements, des équipements, des accessoires et des services dans le monde entier.',
                ],
                'website_url' => 'https://www.nike.com',
            ],
            [
                'name' => 'Adidas',
                'description' => [
                    'en' => 'Adidas AG is a German multinational corporation, founded and headquartered in Herzogenaurach, Germany, that designs and manufactures shoes, clothing and accessories.',
                    'es' => 'Adidas AG es una corporación multinacional alemana, fundada y con sede en Herzogenaurach, Alemania, que diseña y fabrica zapatos, ropa y accesorios.',
                    'fr' => 'Adidas AG est une société multinationale allemande, fondée et basée à Herzogenaurach, en Allemagne, qui conçoit et fabrique des chaussures, des vêtements et des accessoires.',
                ],
                'website_url' => 'https://www.adidas.com',
            ],
            [
                'name' => 'Puma',
                'description' => [
                    'en' => 'Puma SE is a German multinational corporation that designs and manufactures athletic and casual footwear, apparel and accessories.',
                    'es' => 'Puma SE es una corporación multinacional alemana que diseña y fabrica calzado deportivo y casual, ropa y accesorios.',
                    'fr' => 'Puma SE est une société multinationale allemande qui conçoit et fabrique des chaussures de sport et décontractées, des vêtements et des accessoires.',
                ],
                'website_url' => 'https://www.puma.com',
            ],
            [
                'name' => 'Under Armour',
                'description' => [
                    'en' => 'Under Armour, Inc. is an American company that manufactures footwear, sports and casual apparel.',
                    'es' => 'Under Armour, Inc. es una empresa estadounidense que fabrica calzado, ropa deportiva y casual.',
                    'fr' => 'Under Armour, Inc. est une entreprise américaine qui fabrique des chaussures, des vêtements de sport et décontractés.',
                ],
                'website_url' => 'https://www.underarmour.com',
            ],
            [
                'name' => 'Levi\'s',
                'description' => [
                    'en' => 'Levi Strauss & Co. is an American clothing company known worldwide for its Levi\'s brand of denim jeans.',
                    'es' => 'Levi Strauss & Co. es una empresa de ropa estadounidense conocida mundialmente por su marca de jeans vaqueros Levi\'s.',
                    'fr' => 'Levi Strauss & Co. est une entreprise de vêtements américaine connue dans le monde entier pour sa marque de jeans Levi\'s.',
                ],
                'website_url' => 'https://www.levi.com',
            ],
            [
                'name' => 'Zara',
                'description' => [
                    'en' => 'Zara is a Spanish apparel retailer based in Arteixo, Galicia, Spain. It is the flagship chain store of the Inditex group.',
                    'es' => 'Zara es un minorista de ropa español con sede en Arteixo, Galicia, España. Es la tienda insignia del grupo Inditex.',
                    'fr' => 'Zara est un détaillant de vêtements espagnol basé à Arteixo, en Galice, en Espagne. C\'est la chaîne phare du groupe Inditex.',
                ],
                'website_url' => 'https://www.zara.com',
            ],
            [
                'name' => 'H&M',
                'description' => [
                    'en' => 'H&M is a Swedish multinational clothing-retail company known for its fast-fashion clothing for men, women, teenagers and children.',
                    'es' => 'H&M es una empresa multinacional sueca de venta de ropa conocida por su ropa de moda rápida para hombres, mujeres, adolescentes y niños.',
                    'fr' => 'H&M est une entreprise multinationale suédoise de vente de vêtements connue pour ses vêtements de mode rapide pour hommes, femmes, adolescents et enfants.',
                ],
                'website_url' => 'https://www.hm.com',
            ],
            [
                'name' => 'Calvin Klein',
                'description' => [
                    'en' => 'Calvin Klein Inc. is an American fashion house established in 1968. It specializes in underwear, jeans, and ready-to-wear clothing.',
                    'es' => 'Calvin Klein Inc. es una casa de moda estadounidense establecida en 1968. Se especializa en ropa interior, jeans y ropa lista para usar.',
                    'fr' => 'Calvin Klein Inc. est une maison de mode américaine établie en 1968. Elle se spécialise dans les sous-vêtements, les jeans et les vêtements prêts-à-porter.',
                ],
                'website_url' => 'https://www.calvinklein.com',
            ],
            [
                'name' => 'Tommy Hilfiger',
                'description' => [
                    'en' => 'Tommy Hilfiger is an American premium lifestyle brand that provides premium quality, value and style to consumers worldwide.',
                    'es' => 'Tommy Hilfiger es una marca de estilo de vida premium estadounidense que proporciona calidad, valor y estilo premium a los consumidores de todo el mundo.',
                    'fr' => 'Tommy Hilfiger est une marque de mode de vie premium américaine qui offre qualité, valeur et style premium aux consommateurs du monde entier.',
                ],
                'website_url' => 'https://www.tommy.com',
            ],
            [
                'name' => 'Ralph Lauren',
                'description' => [
                    'en' => 'Ralph Lauren Corporation is an American fashion company producing products ranging from the mid-range to the luxury segments.',
                    'es' => 'Ralph Lauren Corporation es una empresa de moda estadounidense que produce productos que van desde el segmento medio hasta el de lujo.',
                    'fr' => 'Ralph Lauren Corporation est une entreprise de mode américaine produisant des produits allant du segment moyen au segment de luxe.',
                ],
                'website_url' => 'https://www.ralphlauren.com',
            ],
            // Beauty & Personal Care
            [
                'name' => 'L\'Oréal',
                'description' => [
                    'en' => 'L\'Oréal S.A. is a French personal care company headquartered in Clichy, Hauts-de-Seine with a registered office in Paris.',
                    'es' => 'L\'Oréal S.A. es una empresa francesa de cuidado personal con sede en Clichy, Hauts-de-Seine con una oficina registrada en París.',
                    'fr' => 'L\'Oréal S.A. est une entreprise française de soins personnels basée à Clichy, Hauts-de-Seine avec un siège social à Paris.',
                ],
                'website_url' => 'https://www.loreal.com',
            ],
            [
                'name' => 'Estée Lauder',
                'description' => [
                    'en' => 'The Estée Lauder Companies Inc. is an American multinational cosmetics company, one of the world\'s largest manufacturers and marketers of prestige skincare, makeup, fragrance and hair care products.',
                    'es' => 'The Estée Lauder Companies Inc. es una empresa multinacional estadounidense de cosméticos, uno de los mayores fabricantes y comercializadores mundiales de productos de cuidado de la piel, maquillaje, fragancias y cuidado del cabello de prestigio.',
                    'fr' => 'The Estée Lauder Companies Inc. est une entreprise cosmétique multinationale américaine, l\'un des plus grands fabricants et marketeurs mondiaux de produits de soins de la peau, maquillage, parfums et soins capillaires de prestige.',
                ],
                'website_url' => 'https://www.esteelauder.com',
            ],
            [
                'name' => 'MAC Cosmetics',
                'description' => [
                    'en' => 'MAC Cosmetics is a Canadian cosmetics manufacturer founded in Toronto and headquartered in New York City.',
                    'es' => 'MAC Cosmetics es un fabricante de cosméticos canadiense fundado en Toronto y con sede en la ciudad de Nueva York.',
                    'fr' => 'MAC Cosmetics est un fabricant de cosmétiques canadien fondé à Toronto et basé à New York.',
                ],
                'website_url' => 'https://www.maccosmetics.com',
            ],
            [
                'name' => 'Clinique',
                'description' => [
                    'en' => 'Clinique Laboratories, LLC is an American manufacturer of skincare, cosmetics, toiletries and fragrances, usually sold in high-end department stores.',
                    'es' => 'Clinique Laboratories, LLC es un fabricante estadounidense de cuidado de la piel, cosméticos, artículos de tocador y fragancias, generalmente vendidos en grandes almacenes de alta gama.',
                    'fr' => 'Clinique Laboratories, LLC est un fabricant américain de soins de la peau, cosmétiques, articles de toilette et parfums, généralement vendus dans les grands magasins haut de gamme.',
                ],
                'website_url' => 'https://www.clinique.com',
            ],
            // Home & Kitchen
            [
                'name' => 'IKEA',
                'description' => [
                    'en' => 'IKEA is a Swedish multinational conglomerate that designs and sells ready-to-assemble furniture, kitchen appliances and home accessories.',
                    'es' => 'IKEA es un conglomerado multinacional sueco que diseña y vende muebles listos para ensamblar, electrodomésticos de cocina y accesorios para el hogar.',
                    'fr' => 'IKEA est un conglomérat multinational suédois qui conçoit et vend des meubles en kit, des appareils électroménagers et des accessoires pour la maison.',
                ],
                'website_url' => 'https://www.ikea.com',
            ],
            [
                'name' => 'KitchenAid',
                'description' => [
                    'en' => 'KitchenAid is an American home appliance brand owned by Whirlpool Corporation. The company was started in 1919 by The Hobart Manufacturing Company.',
                    'es' => 'KitchenAid es una marca estadounidense de electrodomésticos propiedad de Whirlpool Corporation. La empresa fue iniciada en 1919 por The Hobart Manufacturing Company.',
                    'fr' => 'KitchenAid est une marque d\'électroménagers américaine appartenant à Whirlpool Corporation. L\'entreprise a été créée en 1919 par The Hobart Manufacturing Company.',
                ],
                'website_url' => 'https://www.kitchenaid.com',
            ],
            [
                'name' => 'Dyson',
                'description' => [
                    'en' => 'Dyson Ltd is a British technology company that designs and manufactures household appliances such as vacuum cleaners, air purifiers, hand dryers, bladeless fans, heaters, hair dryers and lights.',
                    'es' => 'Dyson Ltd es una empresa tecnológica británica que diseña y fabrica electrodomésticos como aspiradoras, purificadores de aire, secadores de manos, ventiladores sin aspas, calentadores, secadores de pelo y luces.',
                    'fr' => 'Dyson Ltd est une entreprise technologique britannique qui conçoit et fabrique des appareils ménagers tels que des aspirateurs, des purificateurs d\'air, des sèche-mains, des ventilateurs sans pales, des radiateurs, des sèche-cheveux et des lumières.',
                ],
                'website_url' => 'https://www.dyson.com',
            ],
            [
                'name' => 'Philips',
                'description' => [
                    'en' => 'Philips is a Dutch multinational conglomerate corporation that was founded in Eindhoven in 1891. It focuses on health technology, consumer electronics, and lighting.',
                    'es' => 'Philips es una corporación multinacional holandesa fundada en Eindhoven en 1891. Se enfoca en tecnología de salud, productos electrónicos de consumo e iluminación.',
                    'fr' => 'Philips est une société multinationale néerlandaise fondée à Eindhoven en 1891. Elle se concentre sur la technologie de la santé, l\'électronique grand public et l\'éclairage.',
                ],
                'website_url' => 'https://www.philips.com',
            ],
            // Automotive
            [
                'name' => 'Tesla',
                'description' => [
                    'en' => 'Tesla, Inc. is an American electric vehicle and clean energy company based in Austin, Texas.',
                    'es' => 'Tesla, Inc. es una empresa estadounidense de vehículos eléctricos y energía limpia con sede en Austin, Texas.',
                    'fr' => 'Tesla, Inc. est une entreprise américaine de véhicules électriques et d\'énergie propre basée à Austin, au Texas.',
                ],
                'website_url' => 'https://www.tesla.com',
            ],
            [
                'name' => 'BMW',
                'description' => [
                    'en' => 'Bayerische Motoren Werke AG, commonly referred to as BMW, is a German multinational manufacturer of luxury vehicles and motorcycles.',
                    'es' => 'Bayerische Motoren Werke AG, comúnmente conocida como BMW, es un fabricante multinacional alemán de vehículos y motocicletas de lujo.',
                    'fr' => 'Bayerische Motoren Werke AG, communément appelé BMW, est un fabricant multinational allemand de véhicules et motos de luxe.',
                ],
                'website_url' => 'https://www.bmw.com',
            ],
            // Sports & Outdoors
            [
                'name' => 'Patagonia',
                'description' => [
                    'en' => 'Patagonia, Inc. is an American retailer of outdoor clothing and gear. It was founded by Yvon Chouinard in 1973.',
                    'es' => 'Patagonia, Inc. es un minorista estadounidense de ropa y equipo para exteriores. Fue fundada por Yvon Chouinard en 1973.',
                    'fr' => 'Patagonia, Inc. est un détaillant américain de vêtements et d\'équipements de plein air. Il a été fondé par Yvon Chouinard en 1973.',
                ],
                'website_url' => 'https://www.patagonia.com',
            ],
            [
                'name' => 'The North Face',
                'description' => [
                    'en' => 'The North Face is an American outdoor recreation products company. The company sponsors professional athletes and organizes expeditions.',
                    'es' => 'The North Face es una empresa estadounidense de productos de recreación al aire libre. La empresa patrocina atletas profesionales y organiza expediciones.',
                    'fr' => 'The North Face est une entreprise américaine de produits de loisirs de plein air. L\'entreprise parraine des athlètes professionnels et organise des expéditions.',
                ],
                'website_url' => 'https://www.thenorthface.com',
            ],
            [
                'name' => 'Columbia',
                'description' => [
                    'en' => 'Columbia Sportswear Company is an American company that manufactures and distributes outerwear, sportswear, and footwear.',
                    'es' => 'Columbia Sportswear Company es una empresa estadounidense que fabrica y distribuye ropa exterior, ropa deportiva y calzado.',
                    'fr' => 'Columbia Sportswear Company est une entreprise américaine qui fabrique et distribue des vêtements d\'extérieur, des vêtements de sport et des chaussures.',
                ],
                'website_url' => 'https://www.columbia.com',
            ],
            // Watches & Jewelry
            [
                'name' => 'Rolex',
                'description' => [
                    'en' => 'Rolex SA is a British-founded Swiss watch designer and manufacturer based in Geneva, Switzerland.',
                    'es' => 'Rolex SA es un diseñador y fabricante de relojes suizo fundado en Gran Bretaña con sede en Ginebra, Suiza.',
                    'fr' => 'Rolex SA est un concepteur et fabricant de montres suisse fondé en Grande-Bretagne basé à Genève, en Suisse.',
                ],
                'website_url' => 'https://www.rolex.com',
            ],
            [
                'name' => 'Omega',
                'description' => [
                    'en' => 'Omega SA is a Swiss luxury watchmaker based in Biel/Bienne, Switzerland. Founded by Louis Brandt in La Chaux-de-Fonds in 1848.',
                    'es' => 'Omega SA es un fabricante de relojes de lujo suizo con sede en Biel/Bienne, Suiza. Fundado por Louis Brandt en La Chaux-de-Fonds en 1848.',
                    'fr' => 'Omega SA est un fabricant de montres de luxe suisse basé à Biel/Bienne, en Suisse. Fondé par Louis Brandt à La Chaux-de-Fonds en 1848.',
                ],
                'website_url' => 'https://www.omegawatches.com',
            ],
            [
                'name' => 'Tiffany & Co.',
                'description' => [
                    'en' => 'Tiffany & Co. is an American luxury jewelry and specialty retailer, headquartered in New York City.',
                    'es' => 'Tiffany & Co. es un minorista estadounidense de joyería de lujo y especialidades, con sede en la ciudad de Nueva York.',
                    'fr' => 'Tiffany & Co. est un détaillant américain de bijoux de luxe et de spécialités, basé à New York.',
                ],
                'website_url' => 'https://www.tiffany.com',
            ],
            // Food & Beverage
            [
                'name' => 'Nespresso',
                'description' => [
                    'en' => 'Nespresso is a brand of coffee machines and coffee capsules made by Nestlé. The machines brew espresso and coffee from coffee capsules.',
                    'es' => 'Nespresso es una marca de máquinas de café y cápsulas de café fabricadas por Nestlé. Las máquinas preparan espresso y café a partir de cápsulas de café.',
                    'fr' => 'Nespresso est une marque de machines à café et de capsules de café fabriquées par Nestlé. Les machines préparent de l\'espresso et du café à partir de capsules de café.',
                ],
                'website_url' => 'https://www.nespresso.com',
            ],
            [
                'name' => 'Keurig',
                'description' => [
                    'en' => 'Keurig Dr Pepper is an American beverage and coffee maker company. The company manufactures coffee brewers and single-serve coffee pods.',
                    'es' => 'Keurig Dr Pepper es una empresa estadounidense de bebidas y fabricante de café. La empresa fabrica cafeteras y cápsulas de café de una sola porción.',
                    'fr' => 'Keurig Dr Pepper est une entreprise américaine de boissons et de fabricant de café. L\'entreprise fabrique des machines à café et des dosettes de café à portion unique.',
                ],
                'website_url' => 'https://www.keurig.com',
            ],
        ];

        foreach ($brands as $brandData) {
            $brand = Brand::updateOrCreate(
                ['name' => $brandData['name']],
                [
                    'attribute_data' => collect([
                        'description' => new TranslatedText(collect([
                            'en' => new Text($brandData['description']['en']),
                            'es' => new Text($brandData['description']['es'] ?? $brandData['description']['en']),
                            'fr' => new Text($brandData['description']['fr'] ?? $brandData['description']['en']),
                        ])),
                        'website_url' => new Text($brandData['website_url'] ?? ''),
                    ]),
                ]
            );

            // Ensure each brand has a logo image.
            $this->ensureBrandLogo($brand);

            $this->command->info("  ✓ Created brand: {$brand->name}");
        }

        // Backfill any other brands created by factories/other seeders which are missing a logo.
        $missingLogoCount = Brand::query()
            ->get()
            ->filter(fn (Brand $b) => !$b->getFirstMedia('logo'))
            ->count();

        if ($missingLogoCount > 0) {
            $this->command->info("🖼️ Backfilling logos for {$missingLogoCount} existing brands...");
            Brand::query()->chunk(100, function ($chunk) {
                foreach ($chunk as $brand) {
                    $this->ensureBrandLogo($brand);
                }
            });
        }

        $this->command->info('✅ Brand seeding completed!');
        $this->command->info('   Note: Brand logos can also be replaced via media upload in the admin panel.');
    }
}

