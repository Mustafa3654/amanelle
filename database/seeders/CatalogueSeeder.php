<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\FragranceReference;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Seeder;

/**
 * Example catalogue drawn from what @amanelle_beauty actually posts — real
 * houses, real lines, real dupe mappings. Prices are illustrative USD
 * placeholders and need replacing with Amanelle's own before launch.
 */
class CatalogueSeeder extends Seeder
{
    public function run(): void
    {
        $categories = $this->categories();
        $brands = $this->brands();

        foreach ($this->products() as $data) {
            $product = Product::updateOrCreate(
                ['slug' => $data['slug']],
                [
                    'brand_id' => $brands[$data['brand']]->id,
                    'category_id' => $categories[$data['category']]->id,
                    'type' => $data['type'],
                    'name' => $data['name'],
                    'short_description' => $data['short'],
                    'description' => $data['description'] ?? $data['short'],
                    'longevity' => $data['longevity'] ?? null,
                    'projection' => $data['projection'] ?? null,
                    'gender' => $data['gender'] ?? null,
                    'notes_top' => $data['notes'][0] ?? null,
                    'notes_heart' => $data['notes'][1] ?? null,
                    'notes_base' => $data['notes'][2] ?? null,
                    'skin_types' => $data['skin_types'] ?? null,
                    'concerns' => $data['concerns'] ?? null,
                    'is_active' => true,
                    'is_featured' => $data['featured'] ?? false,
                    'published_at' => now(),
                ]
            );

            foreach ($data['variants'] as $i => $variant) {
                $row = ProductVariant::updateOrCreate(
                    ['sku' => $variant['sku']],
                    [
                        'product_id' => $product->id,
                        'volume_ml' => $variant['ml'] ?? null,
                        'concentration' => $variant['conc'] ?? null,
                        'shade_name' => $variant['shade'] ?? null,
                        'shade_hex' => $variant['hex'] ?? null,
                        // Stored in the base currency (USD). Everything the
                        // customer sees is converted at display time from the
                        // admin-editable rate.
                        'price' => $variant['price'],
                        'compare_at_price' => $variant['was'] ?? null,
                        'currency' => 'USD',
                        'sort_order' => $i,
                        'is_active' => true,
                    ]
                );

                Inventory::updateOrCreate(
                    ['product_variant_id' => $row->id, 'market' => 'LB'],
                    ['quantity' => $variant['lb'] ?? 6, 'reserved' => 0, 'low_stock_threshold' => 5]
                );
            }

            foreach ($data['inspired_by'] ?? [] as $reference) {
                FragranceReference::updateOrCreate(
                    [
                        'product_id' => $product->id,
                        'designer_house' => $reference[0],
                        'original_name' => $reference[1],
                    ],
                    ['original_price' => $reference[2] ?? null, 'currency' => 'USD']
                );
            }
        }
    }

    /** @return array<string, Category> */
    private function categories(): array
    {
        $rows = [
            'perfumes' => ['en' => 'Perfumes', 'ar' => 'عطور'],
            'skincare' => ['en' => 'Skincare', 'ar' => 'العناية بالبشرة'],
            'makeup' => ['en' => 'Makeup', 'ar' => 'مكياج'],
            'gift-sets' => ['en' => 'Gift sets', 'ar' => 'مجموعات الهدايا'],
        ];

        $out = [];

        foreach ($rows as $slug => $name) {
            $out[$slug] = Category::updateOrCreate(
                ['slug' => $slug],
                ['name' => $name, 'is_active' => true]
            );
        }

        return $out;
    }

    /** @return array<string, Brand> */
    private function brands(): array
    {
        // origin_country is where the stock is made, not a market we ship to.
        // Amanelle sells in Lebanon only.
        $rows = [
            'assaf' => ['en' => 'ASSAF', 'ar' => 'عساف', 'country' => 'SA'],
            'gissah' => ['en' => 'Gissah', 'ar' => 'قصة', 'country' => 'SA'],
            'gulf-orchid' => ['en' => 'Gulf Orchid', 'ar' => 'جلف أوركيد', 'country' => 'AE'],
            'match' => ['en' => 'Match', 'ar' => 'ماتش', 'country' => 'SA'],
            'maison-alhambra' => ['en' => 'Maison Alhambra', 'ar' => 'ميزون الحمراء', 'country' => 'AE'],
            'some-by-mi' => ['en' => 'Some By Mi', 'ar' => 'سام باي مي', 'country' => 'KR'],
            'anua' => ['en' => 'Anua', 'ar' => 'أنوا', 'country' => 'KR'],
            'medicube' => ['en' => 'medicube', 'ar' => 'ميديكيوب', 'country' => 'KR'],
        ];

        $out = [];
        $order = 0;

        foreach ($rows as $slug => $row) {
            $out[$slug] = Brand::updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => ['en' => $row['en'], 'ar' => $row['ar']],
                    'origin_country' => $row['country'],
                    'is_authorised_stockist' => true,
                    'sort_order' => $order++,
                    'is_active' => true,
                ]
            );
        }

        return $out;
    }

    private function products(): array
    {
        return [
            [
                'slug' => 'assaf-pink-lady',
                'brand' => 'assaf', 'category' => 'perfumes', 'type' => 'fragrance',
                'name' => ['en' => 'Pink Lady', 'ar' => 'بينك ليدي'],
                'short' => [
                    'en' => 'From the ASSAF Lady Collection — soft, powdery and long-wearing.',
                    'ar' => 'من مجموعة ليدي من عساف — ناعم، بودري، وثباته طويل.',
                ],
                'gender' => 'women', 'longevity' => 4, 'projection' => 4, 'featured' => true,
                'notes' => [['Pear', 'Bergamot'], ['Rose', 'Peony'], ['Musk', 'Vanilla']],
                'variants' => [
                    ['sku' => 'ASF-PL-100-EDP', 'ml' => 100, 'conc' => 'edp', 'price' => 49, 'was' => 64, 'lb' => 6],
                    ['sku' => 'ASF-PL-50-EDP', 'ml' => 50, 'conc' => 'edp', 'price' => 31, 'lb' => 3],
                ],
            ],
            [
                'slug' => 'assaf-miss-sakura',
                'brand' => 'assaf', 'category' => 'perfumes', 'type' => 'fragrance',
                'name' => ['en' => 'Miss Sakura', 'ar' => 'مس ساكورا'],
                'short' => [
                    'en' => 'Cherry blossom and white musk, from the Lady Collection.',
                    'ar' => 'زهر الكرز والمسك الأبيض، من مجموعة ليدي.',
                ],
                'gender' => 'women', 'longevity' => 3, 'projection' => 3,
                'notes' => [['Cherry blossom'], ['Jasmine'], ['White musk']],
                'variants' => [
                    ['sku' => 'ASF-MS-100-EDP', 'ml' => 100, 'conc' => 'edp', 'price' => 47, 'lb' => 5],
                ],
            ],
            [
                'slug' => 'assaf-noble-intense',
                'brand' => 'assaf', 'category' => 'perfumes', 'type' => 'fragrance',
                'name' => ['en' => 'Noble Intense', 'ar' => 'نوبل إنتنس'],
                'short' => [
                    'en' => 'From The Originals — woody, warm, built for long days.',
                    'ar' => 'من ذا أوريجينالز — خشبي ودافئ، لنهار طويل.',
                ],
                'gender' => 'men', 'longevity' => 5, 'projection' => 4, 'featured' => true,
                'notes' => [['Bergamot', 'Cardamom'], ['Leather'], ['Oud', 'Amber']],
                'variants' => [
                    ['sku' => 'ASF-NI-100-EDP', 'ml' => 100, 'conc' => 'edp', 'price' => 56, 'lb' => 4],
                ],
            ],
            [
                'slug' => 'gulf-orchid-pink-marshmallow',
                'brand' => 'gulf-orchid', 'category' => 'perfumes', 'type' => 'fragrance',
                'name' => ['en' => 'Pink Marshmallow', 'ar' => 'بينك مارشميلو'],
                'short' => [
                    'en' => 'Sweet gourmand — the affordable answer to Kayali Yum.',
                    'ar' => 'حلو وجورماند — البديل الاقتصادي لـ Kayali Yum.',
                ],
                'gender' => 'women', 'longevity' => 4, 'projection' => 5, 'featured' => true,
                'notes' => [['Marshmallow'], ['Vanilla', 'Praline'], ['Tonka', 'Musk']],
                'inspired_by' => [['Kayali', 'Yum Pistachio Gelato 33', 118]],
                'variants' => [
                    ['sku' => 'GO-PM-100-EDP', 'ml' => 100, 'conc' => 'edp', 'price' => 39, 'was' => 51, 'lb' => 8],
                ],
            ],
            [
                'slug' => 'gulf-orchid-royal-rose',
                'brand' => 'gulf-orchid', 'category' => 'perfumes', 'type' => 'fragrance',
                'name' => ['en' => 'Royal Rose', 'ar' => 'رويال روز'],
                'short' => [
                    'en' => 'A full rose with real staying power.',
                    'ar' => 'وردة كاملة بثبات فعلي.',
                ],
                'gender' => 'women', 'longevity' => 4, 'projection' => 3,
                'notes' => [['Rose', 'Litchi'], ['Peony'], ['Sandalwood', 'Musk']],
                'variants' => [
                    ['sku' => 'GO-RR-100-EDP', 'ml' => 100, 'conc' => 'edp', 'price' => 37, 'lb' => 5],
                ],
            ],
            [
                'slug' => 'match-bell',
                'brand' => 'match', 'category' => 'perfumes', 'type' => 'fragrance',
                'name' => ['en' => 'Match Bell', 'ar' => 'ماتش بيل'],
                'short' => [
                    'en' => 'Saudi-made alternative to a French classic, at a fraction of the price.',
                    'ar' => 'بديل سعودي لكلاسيكية فرنسية، بجزء بسيط من السعر.',
                ],
                'gender' => 'women', 'longevity' => 4, 'projection' => 4, 'featured' => true,
                'notes' => [['Blackcurrant', 'Pear'], ['Iris', 'Orange blossom'], ['Praline', 'Vanilla', 'Patchouli']],
                'inspired_by' => [['Lancôme', 'La Vie Est Belle', 145]],
                'variants' => [
                    ['sku' => 'MTC-BELL-50-EDP', 'ml' => 50, 'conc' => 'edp', 'price' => 25, 'was' => 35, 'lb' => 10],
                ],
            ],
            [
                'slug' => 'maison-alhambra-vogue-rouge',
                'brand' => 'maison-alhambra', 'category' => 'perfumes', 'type' => 'fragrance',
                'name' => ['en' => 'Vogue Rouge', 'ar' => 'فوغ روج'],
                'short' => [
                    'en' => 'Red and gold, spicy and bold — an evening scent.',
                    'ar' => 'أحمر وذهبي، حار وجريء — عطر سهرة.',
                ],
                'gender' => 'women', 'longevity' => 5, 'projection' => 5,
                'notes' => [['Saffron'], ['Rose', 'Jasmine'], ['Amberwood', 'Oud']],
                'variants' => [
                    ['sku' => 'MA-VR-100-EDP', 'ml' => 100, 'conc' => 'edp', 'price' => 42, 'lb' => 4],
                ],
            ],
            [
                'slug' => 'gissah-one-and-only',
                'brand' => 'gissah', 'category' => 'perfumes', 'type' => 'fragrance',
                'name' => ['en' => 'One & Only', 'ar' => 'وان آند أونلي'],
                'short' => [
                    'en' => 'Every bottle carries a QR seal you can verify yourself.',
                    'ar' => 'كل عبوة عليها ختم QR فيكِ تتأكدي منه بنفسك.',
                ],
                'gender' => 'unisex', 'longevity' => 5, 'projection' => 4,
                'notes' => [['Pineapple', 'Bergamot'], ['Birch'], ['Ambergris', 'Musk']],
                'variants' => [
                    ['sku' => 'GIS-OAO-80-EDP', 'ml' => 80, 'conc' => 'edp', 'price' => 53, 'lb' => 2],
                ],
            ],
            [
                'slug' => 'some-by-mi-retinol-intense-eye-cream',
                'brand' => 'some-by-mi', 'category' => 'skincare', 'type' => 'skincare',
                'name' => ['en' => 'Retinol Intense Eye Cream', 'ar' => 'كريم العين المكثف بالريتينول'],
                'short' => [
                    'en' => 'For fine lines and dark circles — the one from the reels.',
                    'ar' => 'للخطوط الرفيعة والهالات — يلي بالريلز.',
                ],
                'featured' => true,
                'skin_types' => ['all', 'mature'],
                'concerns' => ['fine-lines', 'dark-circles', 'firmness'],
                'variants' => [
                    ['sku' => 'SBM-RIEC-30', 'ml' => 30, 'price' => 24, 'was' => 29, 'lb' => 12],
                ],
            ],
            [
                'slug' => 'anua-brightening-pads',
                'brand' => 'anua', 'category' => 'skincare', 'type' => 'skincare',
                'name' => ['en' => 'Brightening Toner Pads', 'ar' => 'باد التونر للإشراق'],
                'short' => [
                    'en' => 'Two steps in one — wipe, glow, done.',
                    'ar' => 'خطوتين بوحدة — امسحي، اشرقي، خلصنا.',
                ],
                'skin_types' => ['oily', 'combination'],
                'concerns' => ['dullness', 'texture'],
                'variants' => [
                    ['sku' => 'ANU-BTP-160', 'ml' => 160, 'price' => 19, 'lb' => 3],
                ],
            ],
            [
                'slug' => 'medicube-glow-vanilla-mist',
                'brand' => 'medicube', 'category' => 'skincare', 'type' => 'skincare',
                'name' => ['en' => 'Glow Vanilla Hair & Body Mist', 'ar' => 'ميست الشعر والجسم غلو فانيلا'],
                'short' => [
                    'en' => 'Vanilla mist with kojic acid and hyaluronic acid.',
                    'ar' => 'ميست فانيلا مع كوجيك أسيد وهيالورونيك.',
                ],
                'concerns' => ['hydration'],
                'variants' => [
                    ['sku' => 'MDC-GV-250', 'ml' => 250, 'price' => 18, 'lb' => 9],
                ],
            ],
            [
                'slug' => 'amanelle-signature-lipstick',
                'brand' => 'assaf', 'category' => 'makeup', 'type' => 'makeup',
                'name' => ['en' => 'Signature Satin Lipstick', 'ar' => 'أحمر شفاه ساتان سيجنتشر'],
                'short' => [
                    'en' => 'Four shades, satin finish, buildable colour.',
                    'ar' => 'أربع درجات، لمسة ساتان، لون بيتدرّج.',
                ],
                'featured' => true,
                'variants' => [
                    ['sku' => 'AMN-LIP-ROSE', 'shade' => ['en' => 'Desert Rose', 'ar' => 'وردة الصحراء'], 'hex' => '#c96f75', 'price' => 21, 'lb' => 6],
                    ['sku' => 'AMN-LIP-DATE', 'shade' => ['en' => 'Date Brown', 'ar' => 'بني التمر'], 'hex' => '#8c4a3a', 'price' => 21, 'lb' => 5],
                    ['sku' => 'AMN-LIP-OUD', 'shade' => ['en' => 'Oud Plum', 'ar' => 'برقوقي عودي'], 'hex' => '#6b2f45', 'price' => 21, 'lb' => 2],
                    ['sku' => 'AMN-LIP-SAND', 'shade' => ['en' => 'Sandstone', 'ar' => 'حجر رملي'], 'hex' => '#c08e6d', 'price' => 21, 'lb' => 0],
                ],
            ],
            [
                'slug' => 'assaf-discovery-set',
                'brand' => 'assaf', 'category' => 'gift-sets', 'type' => 'fragrance',
                'name' => ['en' => 'ASSAF Discovery Set', 'ar' => 'مجموعة عساف الاستكشافية'],
                'short' => [
                    'en' => 'Five miniatures — the easiest way to find your one.',
                    'ar' => 'خمس عبوات صغيرة — أسهل طريقة تلاقي عطرك.',
                ],
                'gender' => 'unisex', 'longevity' => 4, 'projection' => 3,
                'variants' => [
                    ['sku' => 'ASF-DISC-5X10', 'ml' => 10, 'conc' => 'edp', 'price' => 40, 'was' => 53, 'lb' => 4],
                ],
            ],
        ];
    }
}
