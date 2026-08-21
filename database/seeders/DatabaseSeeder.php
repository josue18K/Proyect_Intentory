<?php

namespace Database\Seeders;

use App\Models\{Branch, Category, Inventory, InventoryMovement, Product, User};
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $admin = User::factory()->create([
            'name' => 'Josué Administrador',
            'email' => 'josuern@gmail.com',
            'password' => Hash::make('Liuva@admin1348033'),
            'role' => 'administrador',
        ]);
        $categories = collect();
        $branches = collect(['TIENDA IMPORTACIONES LIUVA - PAUZA', 'TIENDA IMPORTACIONES LIUVA MUJER'])
            ->mapWithKeys(fn ($name) => [$name => Branch::create(['name' => $name, 'slug' => str($name)->slug()])]);

        // Catalog format: category|name|sale price|initial stock at Pauza.
        $catalog = <<<'CATALOG'
Limpieza|Limpiatodo 1Lt|2.19|0
Limpieza|Lejía 1Lt|2.49|0
Limpieza|Detergente líquido 1Lt|6.49|0
Limpieza|Lavavajilla líquida 1Lt|4.99|0
Limpieza|Jabón líquido 400ml|3.99|0
Limpieza|Saca sarro 1Lt|3.99|0
Limpieza|Ácido 1Lt|3.99|0
Limpieza|Saca grasa 750ml gatillo|6.49|0
Limpieza|Saca grasa 1Lt|0|0
Limpieza|Shampoo carro 750ml gatillo|6.49|0
Limpieza|Silicona llantas 750ml gatillo|0|0
Limpieza|Silicona blanca 750ml gatillo|0|0
Limpieza|Limpiavidrios 750ml gatillo|6.49|0
Limpieza|Silicona VISTONY|7.99|0
Limpieza|Quitamanchas 1Lt|4.49|0
Limpieza|Quitamanchas 650ml|2.79|0
Limpieza|Desinfectante Pino gel 1Lt|2.99|0
Limpieza|Desatorador líquido 1Lt|3.99|0
Limpieza|Suavizante 1Lt|5.49|0
Limpieza|Alcohol gel 400ml|4.99|0
Limpieza|Lejía GL|7.99|0
Limpieza|Limpia todo GL|7.99|0
Limpieza|Suavizante GL|17.49|0
Limpieza|Quitamanchas GL|14.90|0
Limpieza|Lavavajilla líquida GL|13.99|0
Limpieza|Desinfectante Pino GL|10.99|0
Limpieza|Saca sarro GL|12.90|0
Limpieza|Ácido Doméstico GL|12.90|0
Limpieza|Limpiavidrios GL|8.99|0
Limpieza|Detergente líquido GL|18.90|0
Limpieza|Jabón Líquido GL|12.49|0
Hogar|EDREDÓN CARNERO|34.99|0
Hogar|TOALLA ALGODÓN|9.49|0
Hogar|SÁBANA PIEL DURAZNO|21.90|0
Hogar|MANTA PIEL DURAZNO|20.90|0
Hogar|SÁBANA NANCY DELGADA 2PLZ|24.90|0
Hogar|SÁBANAS NANCY 2PLZ|27.99|0
Hogar|COBERTOR DE COLCHÓN|24.90|0
Hogar|ALMOHADAS BAMBOO X UNIDAD|16.90|0
Hogar|ALMOHADAS BAMBOO X PAR|33.80|0
Limpieza|LAVAVAJILLA EN PASTA 1KG|4.99|0
Limpieza|PASTILLA INODORO|2.49|0
Cuidado personal|DOCTOR TRIPLE ACCIÓN|4.49|0
Cuidado personal|DOCTOR CARBÓN|5.49|0
Cuidado personal|DOCTOR WHITENING|4.79|0
Cuidado personal|DOCTOR SENSITIVE|4.79|0
Cuidado personal|CREMA DENTAL NIÑO|2.99|0
Cuidado personal|JABÓN TOKE FRESH|1.19|0
Hogar|SERVILLETA TISU|1.49|0
Papelería|PAPEL BINGO|12.49|0
Papelería|PAPEL MIMO|10.90|0
Papelería|PAPEL TOALLA IVAN|2.39|0
Papelería|PAPEL TOALLA SUPER|2.19|0
Electricidad|FOCO 60W|9.99|0
Electricidad|FOCO 50W|7.99|0
Electricidad|FOCO 40W|7.49|0
Electricidad|FOCO 30W|6.99|0
Electricidad|FOCO 20W|3.99|0
Ferretería|CINTA EMBALAJE 400Y|8.99|0
Ferretería|CINTA EMBALAJE 200Y|5.99|0
Limpieza|ALCOHOL 96° 1LT|8.99|0
Limpieza|ALCOHOL 70° 1LT|8.49|0
Limpieza|ALCOHOL 70° CHICO|0.99|0
Limpieza|ALCOHOL 96° CHICO|0.99|0
Limpieza|BICARBONATO 100GR|1.99|0
Limpieza|ÁCIDO BÓRICO 100 GR|2.49|0
Cuidado personal|PAÑITOS 200 PZAS|7.99|0
Cuidado personal|PAÑITOS 184 PZAS|7.49|0
Cuidado personal|PAÑITOS 120 PZAS|5.99|0
Cuidado personal|PAÑITOS 100 PZAS|4.49|0
Cuidado personal|PAÑITOS 25 PZAS|1.49|0
Cuidado personal|PAÑITOS 12 PZAS|1.19|0
Cuidado personal|DESMAQUILLANTE|2.19|0
Hogar|JARRA 6LT + 3 VASOS|9.99|0
Hogar|JARRA 5LT + 3 VASOS|9.49|0
Hogar|LAVACUELA|2.99|0
Hogar|TAPER PVC VÁLVULA 1.5LT|2.49|0
Hogar|REPOSTERO 6KG|7.99|0
Hogar|SALSERO 600ML X1|2.19|0
Hogar|SALSERO 480 ML X1|1.99|0
Hogar|ESCOBA LORITO|7.49|0
Hogar|RECOGEDOR|1.99|0
Hogar|TACHO BASURA|0|0
Hogar|VERDULERO 4 PISOS|28.90|0
Hogar|CESTO CON ASA CUERO|9.90|0
Hogar|BANCO CHICO|5.99|0
Hogar|BANCO RATÁN|6.99|0
Hogar|BANCO COLORES GRANDE|7.49|0
Hogar|TINA RECTANGULAR|6.49|0
Hogar|TINA OVAL|9.90|0
Hogar|GANCHOS PINZA 16PZ|3.49|0
Hogar|PORTAVAJILLA CHICO|21.50|0
Hogar|PORTAVAJILLA GRANDE CHEF|32.90|0
Hogar|GANCHOS CLOSET ADULTO|4.99|0
Hogar|GANCHOS CLOSET NIÑOS|3.99|0
Hogar|JARRA 1LT COLOR|2.49|0
Hogar|ORGANIZADOR DE ROPA|0|0
Hogar|TINA CON ASA OVALADA|0|0
Hogar|SECADOR DE PLATOS METAL|0|0
Hogar|ESTANTE DE BAÑO|34.99|0
Hogar|ESTANTE DE COCINA METAL|104.90|0
Hogar|ESTANTE DE LAVADORA|35.99|0
Electrodomésticos|HERVIDOR ELÉCTRICO METAL|18.99|0
Electrodomésticos|HERVIDOR ELÉCTRICO VIDRIO|27.49|0
Electrodomésticos|HERVIDOR ELÉCTRICO PREMIUM|27.90|0
Cuidado personal|SHAMPO ROMERO|13.90|0
Cuidado personal|SHAMPO H&S TIRA X12|6.49|0
Cuidado personal|ACONDICIONADOR ROMERO|0|0
Cuidado personal|TINTE SHAMPOO NEGRO U/D|1.89|0
Cuidado personal|TINTE SHAMPOO NEGRO BOX|7.99|0
Cuidado personal|JABÓN CHOLITA|1.99|0
Control de plagas|PEGA RATA|1.99|0
Control de plagas|ESPIRAL|2.49|0
Hogar|ORGANIZADOR DE BAÑO|0|0
Decoración|PAPEL TAPIZ ADHESIVO BLANCO|2.99|0
Decoración|PAPEL TAPIZ ADHESIVO RÚSTICO|2.99|0
Decoración|PAPEL TAPIZ ROLLO|4.49|0
Decoración|PAPEL TAPIZ ALUMINIO|4.49|0
Bebés|ELA DIARIAS|2.99|0
Bebés|VEESPER TOALLA MORADA|3.49|0
Bebés|VEESPER TOALLA ROSADA|2.99|0
Bebés|PAÑAL VEESPER 42PZ TALLA XXG|32.90|0
Bebés|PAÑAL VEESPER 46PZ TALLA XG|32.90|0
Bebés|PAÑAL VEESPER 62PZ TALLA M|32.90|0
Bebés|PAÑAL VEESPER G|30.99|0
Bebés|PAÑAL VEESPER PAQUETÓN|45.00|0
Limpieza|HUAYU DETERGENTE 4KG|19.90|0
Limpieza|HUAYU DETERGENTE 1KG|5.49|0
Limpieza|HUAYU DETERGENTE 500GR|3.49|0
Limpieza|DETERGENTE PATITO 140 GR|0.99|0
Limpieza|DETERGENTE TROME 1KG|4.99|0
Limpieza|DETERGENTE TROME 15KG|51.99|0
Organización|ROPERO TELA DISEÑO|54.90|0
Organización|ROPERO NIÑOS|54.90|0
Organización|ROPERO TELA 2 CUERPOS|49.90|0
Organización|ROPERO TELA 3 CUERPOS|54.90|0
Organización|ZAPATERA TELA CHICO|19.90|0
Organización|ZAPATERA MELAMINE|159.90|0
Organización|ZAPATERA COLGADOR MULTIFUNCIONAL|38.90|0
Organización|ZAPATERA ZETA|8.99|0
Organización|ZAPATERA 9 PISOS TELA|34.90|0
Organización|ZAPATERA PLEGABLE 6 NIVELES|54.90|0
Hogar|TENDEDERO DE ROPA|49.90|0
Regalos|MOÑO REGALO 6PZ|3.49|3
Tecnología|AUDÍFONO X19 PLUS|16.90|2
Juguetes|GLOBITOS AUTOMÁTICOS CARNAVAL|3.49|4
Papelería|RESALTADORES 6PZ|5.49|6
Tecnología|AUDÍFONO COLLAR DEPORTIVO|15.90|4
Papelería|MARCADOR PIZARRA 5PZ|4.99|2
Tecnología|CABLE CARGADOR 3 PUNTAS|6.99|14
Papelería|BICOLOR CJ X12|5.49|26
Papelería|TIJERA ESCOLAR 2PZ|1.19|2
Tecnología|PALITO SELFIE|10.90|1
Tecnología|FUNDA AGUA CELULAR|3.49|5
Papelería|LAPICERO 3PZ|1.19|3
Belleza|PESTAÑAS POSTIZAS NORMAL|2.49|13
Papelería|PERFORADOR|5.99|1
Papelería|ENGRAMPADOR|6.99|1
Papelería|LAPICERO CJ 50 U/D|14.90|1
Papelería|MARCADOR PAPEL X12|11.90|1
Belleza|RIZADOR PESTAÑAS LED|5.99|30
Papelería|CARTUCHERA TRALALERO|4.49|6
Tecnología|PORTA CELULAR AUTO|6.99|2
Juguetes|AVIÓN CONTROL|129.90|1
Tecnología|PORTA CELULAR ARO LED|12.90|7
Papelería|INDELEBLE GRUESO BOX|7.90|11
Belleza|MÁQUINA CORTA PELO BRONCE|19.90|8
Cuidado personal|HILO DE DIENTE|1.49|16
Tecnología|AUDÍFONO M90 PRO|15.90|8
Tecnología|AUDÍFONO M100|13.90|23
Tecnología|AUDÍFONO P9 PRO MÁX|15.90|7
Tecnología|RELOJ SMART A5|16.49|30
Tecnología|RELOJ DIGITAL|8.90|6
Belleza|GANCHOS MUJER 3PZ|4.49|1
Hogar|QUITA PELUSA PINGÜINO|4.99|14
Electricidad|PILA AAA PAR|2.19|158
Electricidad|PILA AA PAR|1.99|41
Electricidad|LINTERNITA RECARGABLE|6.99|23
Tecnología|PARLANTE CHICO SU01|11.90|7
Electricidad|LINTERNA CABEZA|8.99|4
CATALOG;

        foreach (explode("\n", trim($catalog)) as $index => $line) {
            [$categoryName, $name, $salePrice, $initialStock] = explode('|', $line);
            $category = $categories[$categoryName] ??= Category::create(['name' => $categoryName, 'slug' => str($categoryName)->slug()]);
            $product = Product::create([
                'category_id' => $category->id,
                'internal_code' => 'LIU-' . str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT),
                'name' => $name,
                'purchase_price' => 0,
                'sale_price' => (float) $salePrice,
                'minimum_stock' => $initialStock > 0 ? max(3, (int) ceil($initialStock * .15)) : 3,
            ]);
            foreach ($branches as $branch) {
                $quantity = $branch->name === 'TIENDA IMPORTACIONES LIUVA - PAUZA' ? (int) $initialStock : 0;
                Inventory::create(['product_id' => $product->id, 'branch_id' => $branch->id, 'quantity' => $quantity]);
                if ($quantity > 0) {
                    InventoryMovement::create(['product_id' => $product->id, 'branch_id' => $branch->id, 'user_id' => $admin->id, 'type' => 'entrada', 'quantity' => $quantity, 'stock_before' => 0, 'stock_after' => $quantity, 'reason' => 'Carga inicial del catálogo']);
                }
            }
        }
    }
}
