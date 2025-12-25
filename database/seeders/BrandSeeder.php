<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Lunar\Models\Brand;
use Lunar\FieldTypes\Text;
use Lunar\FieldTypes\TranslatedText;

/**
 * Seeder for creating sample brands with logos and descriptions.
 */
class BrandSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🏷️ Creating brands...');

        $brands = [
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

            $this->command->info("  ✓ Created brand: {$brand->name}");
        }

        $this->command->info('✅ Brand seeding completed!');
        $this->command->info('   Note: Brand logos can be added via media upload in the admin panel.');
    }
}

