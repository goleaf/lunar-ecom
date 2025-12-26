<?php

namespace Database\Seeders;

use Database\Factories\BrandFactory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;
use Lunar\Models\Brand;

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
        $this->command->info('Creating brands...');

        $brands = [
            [
                'name' => 'Apple',
                'description' => 'Apple designs consumer electronics, software, and services.',
                'website_url' => 'https://www.apple.com',
            ],
            [
                'name' => 'Samsung',
                'description' => 'Samsung builds phones, TVs, and home electronics.',
                'website_url' => 'https://www.samsung.com',
            ],
            [
                'name' => 'Sony',
                'description' => 'Sony builds electronics and entertainment products.',
                'website_url' => 'https://www.sony.com',
            ],
            [
                'name' => 'Microsoft',
                'description' => 'Microsoft creates software, cloud services, and devices.',
                'website_url' => 'https://www.microsoft.com',
            ],
            [
                'name' => 'Dell',
                'description' => 'Dell produces computers and business hardware.',
                'website_url' => 'https://www.dell.com',
            ],
            [
                'name' => 'HP',
                'description' => 'HP provides computers, printers, and accessories.',
                'website_url' => 'https://www.hp.com',
            ],
            [
                'name' => 'Canon',
                'description' => 'Canon makes cameras and imaging equipment.',
                'website_url' => 'https://www.canon.com',
            ],
            [
                'name' => 'LG',
                'description' => 'LG builds appliances, TVs, and electronics.',
                'website_url' => 'https://www.lg.com',
            ],
            [
                'name' => 'Lenovo',
                'description' => 'Lenovo builds laptops, desktops, and smart devices.',
                'website_url' => 'https://www.lenovo.com',
            ],
            [
                'name' => 'Asus',
                'description' => 'Asus makes laptops, components, and gaming gear.',
                'website_url' => 'https://www.asus.com',
            ],
            [
                'name' => 'Bose',
                'description' => 'Bose focuses on premium audio products.',
                'website_url' => 'https://www.bose.com',
            ],
            [
                'name' => 'JBL',
                'description' => 'JBL offers speakers and headphones for everyday use.',
                'website_url' => 'https://www.jbl.com',
            ],
            [
                'name' => 'Nike',
                'description' => 'Nike designs athletic footwear and apparel.',
                'website_url' => 'https://www.nike.com',
            ],
            [
                'name' => 'Adidas',
                'description' => 'Adidas creates sports footwear and lifestyle gear.',
                'website_url' => 'https://www.adidas.com',
            ],
            [
                'name' => 'Puma',
                'description' => 'Puma delivers athletic and casual footwear.',
                'website_url' => 'https://www.puma.com',
            ],
            [
                'name' => 'Under Armour',
                'description' => 'Under Armour makes performance apparel and footwear.',
                'website_url' => 'https://www.underarmour.com',
            ],
            [
                'name' => 'Levi\'s',
                'description' => 'Levi\'s produces denim and casual apparel.',
                'website_url' => 'https://www.levi.com',
            ],
            [
                'name' => 'Zara',
                'description' => 'Zara offers fast fashion apparel and accessories.',
                'website_url' => 'https://www.zara.com',
            ],
            [
                'name' => 'H&M',
                'description' => 'H&M offers fashion essentials for everyday wear.',
                'website_url' => 'https://www.hm.com',
            ],
            [
                'name' => 'Calvin Klein',
                'description' => 'Calvin Klein is known for modern apparel and basics.',
                'website_url' => 'https://www.calvinklein.com',
            ],
            [
                'name' => 'Tommy Hilfiger',
                'description' => 'Tommy Hilfiger offers classic American style apparel.',
                'website_url' => 'https://www.tommy.com',
            ],
            [
                'name' => 'Ralph Lauren',
                'description' => 'Ralph Lauren designs premium apparel and accessories.',
                'website_url' => 'https://www.ralphlauren.com',
            ],
            [
                'name' => 'L\'Oreal',
                'description' => 'L\'Oreal creates beauty and personal care products.',
                'website_url' => 'https://www.loreal.com',
            ],
            [
                'name' => 'Estee Lauder',
                'description' => 'Estee Lauder develops skincare and cosmetics.',
                'website_url' => 'https://www.esteelauder.com',
            ],
            [
                'name' => 'MAC Cosmetics',
                'description' => 'MAC Cosmetics creates professional makeup products.',
                'website_url' => 'https://www.maccosmetics.com',
            ],
            [
                'name' => 'Clinique',
                'description' => 'Clinique offers dermatologist tested skincare.',
                'website_url' => 'https://www.clinique.com',
            ],
            [
                'name' => 'IKEA',
                'description' => 'IKEA designs ready to assemble home furnishings.',
                'website_url' => 'https://www.ikea.com',
            ],
            [
                'name' => 'KitchenAid',
                'description' => 'KitchenAid makes kitchen appliances and tools.',
                'website_url' => 'https://www.kitchenaid.com',
            ],
            [
                'name' => 'Dyson',
                'description' => 'Dyson designs home appliances and air care.',
                'website_url' => 'https://www.dyson.com',
            ],
            [
                'name' => 'Philips',
                'description' => 'Philips builds health, lighting, and personal care products.',
                'website_url' => 'https://www.philips.com',
            ],
            [
                'name' => 'Tesla',
                'description' => 'Tesla designs electric vehicles and energy products.',
                'website_url' => 'https://www.tesla.com',
            ],
            [
                'name' => 'BMW',
                'description' => 'BMW builds luxury vehicles and motorcycles.',
                'website_url' => 'https://www.bmw.com',
            ],
            [
                'name' => 'Patagonia',
                'description' => 'Patagonia makes outdoor apparel and gear.',
                'website_url' => 'https://www.patagonia.com',
            ],
            [
                'name' => 'The North Face',
                'description' => 'The North Face builds outdoor apparel and equipment.',
                'website_url' => 'https://www.thenorthface.com',
            ],
            [
                'name' => 'Columbia',
                'description' => 'Columbia makes outdoor apparel and footwear.',
                'website_url' => 'https://www.columbia.com',
            ],
            [
                'name' => 'Rolex',
                'description' => 'Rolex designs luxury timepieces.',
                'website_url' => 'https://www.rolex.com',
            ],
            [
                'name' => 'Omega',
                'description' => 'Omega makes Swiss watches and accessories.',
                'website_url' => 'https://www.omegawatches.com',
            ],
            [
                'name' => 'Tiffany & Co.',
                'description' => 'Tiffany & Co. designs fine jewelry and gifts.',
                'website_url' => 'https://www.tiffany.com',
            ],
            [
                'name' => 'Nespresso',
                'description' => 'Nespresso makes coffee machines and capsules.',
                'website_url' => 'https://www.nespresso.com',
            ],
            [
                'name' => 'Keurig',
                'description' => 'Keurig builds coffee brewers and pods.',
                'website_url' => 'https://www.keurig.com',
            ],
        ];

        foreach ($brands as $brandData) {
            $profile = BrandFactory::new()
                ->withProfile(
                    $brandData['name'],
                    $brandData['description'] ?? null,
                    $brandData['website_url'] ?? null
                )
                ->make();

            $brand = Brand::query()->firstOrNew(['name' => $brandData['name']]);
            $brand->attribute_data = $profile->attribute_data;
            $brand->save();

            // Ensure each brand has a logo image.
            $this->ensureBrandLogo($brand);

            $this->command->info("  Created brand: {$brand->name}");
        }

        // Backfill any other brands created by factories/other seeders which are missing a logo.
        $missingLogoCount = Brand::query()
            ->get()
            ->filter(fn (Brand $b) => !$b->getFirstMedia('logo'))
            ->count();

        if ($missingLogoCount > 0) {
            $this->command->info("Backfilling logos for {$missingLogoCount} existing brands...");
            Brand::query()->chunk(100, function ($chunk) {
                foreach ($chunk as $brand) {
                    $this->ensureBrandLogo($brand);
                }
            });
        }

        $this->command->info('Brand seeding completed.');
        $this->command->info('Note: Brand logos can also be replaced via media upload in the admin panel.');
    }
}
