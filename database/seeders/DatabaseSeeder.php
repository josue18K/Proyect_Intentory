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
        $admin = User::create([
            'name' => 'Josué Administrador',
            'email' => 'josuern@gmail.com',
            'password' => Hash::make('Liuva@admin1348033'),
            'role' => 'administrador',
        ]);
        $categories = collect();
        $branches = collect(['TIENDA IMPORTACIONES LIUVA - PAUZA', 'TIENDA IMPORTACIONES LIUVA MUJER'])
            ->mapWithKeys(fn ($name) => [$name => Branch::create(['name' => $name, 'slug' => str($name)->slug()])]);
        $mujerCategories = collect(['Cuidado de la piel', 'Cosméticos', 'Herramientas de aseo personal', 'Arreglo estético', 'Bisutería', 'Dulces', 'Prendas de vestir', 'Menaje', 'Artículos personales', 'Otros']);
        $pauza = $branches['TIENDA IMPORTACIONES LIUVA - PAUZA'];
        $mujer = $branches['TIENDA IMPORTACIONES LIUVA MUJER'];

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
            $branch = $mujerCategories->contains($categoryName) ? $mujer : $pauza;
            $quantity = (int) $initialStock;
            Inventory::create(['product_id' => $product->id, 'branch_id' => $branch->id, 'quantity' => $quantity]);
            if ($quantity > 0) {
                InventoryMovement::create(['product_id' => $product->id, 'branch_id' => $branch->id, 'user_id' => $admin->id, 'type' => 'entrada', 'quantity' => $quantity, 'stock_before' => 0, 'stock_after' => $quantity, 'reason' => 'Carga inicial del catálogo']);
            }
        }

        $additionalCatalog = <<<'CATALOG'
Cuidado de la piel|COLONIA + CREMA CORPORAL 250ML|17.99
Cuidado de la piel|CREMA HIDRATANTE WOKALI|17.90
Cuidado de la piel|EXFOLIANTE DE LABIOS|14.90
Cuidado de la piel|EXFOLIANTE GEL WOKALI|9.90
Cuidado de la piel|KIT LIMPIADOR FACIAL MADISON|24.90
Cuidado de la piel|LIMPIADOR FACIAL FACEWASH DR RASHEL|14.90
Cuidado de la piel|MASCARILLA FACIAL WOKALI|2.99
Cuidado de la piel|PARCHE DE OJOS|16.90
Cuidado de la piel|PROTECTOR SOLAR ESTELIN|19.90
Cuidado de la piel|SUERO DE LECHE DE BURRA|14.90
Cosméticos|AGB90 POLVO COMPACTO Y SUELTO|13.90
Cosméticos|AGC100B CORRECTOR|6.99
Cosméticos|AGC40 CONTORNO LÍQUIDO|5.49
Cosméticos|AGD170 DELINEADOR PUNTA|4.99
Cosméticos|AGF300 PRIMER BASE|8.49
Cosméticos|AGF400 SPRAY SELLADOR|8.49
Cosméticos|AGI140 ILUMINADOR|5.99
Cosméticos|AGL460 LABIAL KISS|3.49
Cosméticos|AGL560 LABIAL LATINA|3.99
Cosméticos|AGL650 SUBLIME|6.99
Cosméticos|AGL690 LIP GLOSS|6.99
Cosméticos|AGL700 AZUCENA|6.99
Cosméticos|AGP102 POLVO COMPACTO|7.49
Cosméticos|AGP160 POLVOS SUELTOS|8.49
Cosméticos|AGR107 HECHIZADA RIMEL|5.49
Cosméticos|AGR150 RIMEL CON COLÁGENO|4.49
Cosméticos|AGS132 SOMBRAS TERRA|6.99
Cosméticos|AGS140 PALETA DE SOMBRAS|7.49
Cosméticos|AGS800 SOMBRA INTI|14.90
Cosméticos|AGT120 TINTE LABIOS|2.99
Cosméticos|AGZ10 LÁPIZ PARA CEJAS|5.49
Cosméticos|COLONIA 175ML CAJA TUNIES|12.90
Cosméticos|COLONIA BABY MIST 200ML TUNIES|11.90
Cosméticos|COLONIA BODY MIST 175ML TUNIES|11.90
Cosméticos|ESPONJA EXFOLIANTE|1.99
Cosméticos|ESPONJA PARA MAQUILLAJE|1.49
Cosméticos|M2036 LABIAL LÍQUIDO MANSLY|8.49
Cosméticos|M2056 LABIAL LÍQUIDO MANSLY|6.99
Cosméticos|M2058 LIP GLOSS MANSLY|8.99
Cosméticos|PACK 3PZ ESPONJA MAQUILLAJE|2.49
Cosméticos|PERFUME BAHLI HOMBRE|14.90
Cosméticos|PERFUME BAHLI MUJER|14.90
Cosméticos|PERFUME ZAFIRO 50ML|18.90
Cosméticos|PERFUME VELVET 50ML|18.90
Cosméticos|PERFUME SANRIO DISEÑOS 30ML|14.90
Cosméticos|PERFUME DISNEY 50ML|24.90
Cosméticos|PERFUME HELLO KITTY CON BASE 50ML|20.90
Cosméticos|PERFUME BARBIE/UNICORN 50ML|25.90
Cosméticos|PERFUME ROSE&LILN TUBO 35ML|4.99
Cosméticos|TF623 RUBOR LÍQUIDO|4.99
Cosméticos|TS644 BASE TIESIEE|6.99
Cosméticos|TS855 LABIAL BRILLO|4.99
Cosméticos|TS864 ONLY YOU TREESIE|7.49
Cosméticos|TS915 LIP OIL|3.99
Herramientas de aseo personal|AFEITADORAS DE CEJAS 3PZS|2.49
Herramientas de aseo personal|CORTA UÑA 3PZS|2.99
Herramientas de aseo personal|LIMA DE UÑAS|1.49
Herramientas de aseo personal|PINZAS PARA CEJAS 2PZ|1.99
Herramientas de aseo personal|REMOVEDOR DE ESPINILLAS Y PUNTOS NEGROS|1.99
Herramientas de aseo personal|RIZADOR DE METAL|2.99
Herramientas de aseo personal|RIZADOR PREMIUM|3.49
Herramientas de aseo personal|TIJERAS PARA CEJAS CURVADAS|1.99
Aseo personal|BYWIN PACK X2 BEBE|16.90
Aseo personal|CARTUCHERA CEPILLO PASTA TUNIES|7.99
Aseo personal|DESENREDANTE 300ML TUNIES|11.90
Aseo personal|DESMAQUILLANTE KITTY|4.99
Aseo personal|DESODORANTE SKY HOMBRE|7.99
Aseo personal|DESODORANTE SKY MUJER|7.99
Aseo personal|ELA DIARIO|2.49
Aseo personal|ELA NOCTURNA|4.99
Aseo personal|ELA NORMAL|3.49
Aseo personal|ELA ULTRA DELGADA|3.49
Aseo personal|ESPONJA DE DUCHA|2.99
Aseo personal|GEL DE CABELLO 1KG|14.90
Aseo personal|HISOPO KITTY 400PZ|6.99
Aseo personal|JABÓN LÍQUIDO 250ML TUNIES|8.99
Aseo personal|KIT PERFUMER RELOJ VARON|29.99
Aseo personal|PAÑITO 25PZ|1.49
Aseo personal|SHAMPOO 2EN1 FROZEN 236 ML TUNIES|8.99
Aseo personal|SHAMPOO CANAS EN CAJA|14.99
Aseo personal|SHAMPOO KITTY 300ML 3EN1|10.90
Aseo personal|SHAMPOO MARVEL 350ML 3EN1 E 1 TUNIES|15.90
Aseo personal|TALCO 100GR TUNIES|4.99
Aseo personal|TALCO 750GR TUNIES|12.90
Aseo personal|TOALLA CABELLO|3.49
Aseo personal|TOALLITAS HUMEDAS 8PACKS TUNIES|4.99
Aseo personal|ELA NORMAL ECONOPACK 42PZ|12.99
Aseo personal|ELA NOCTURNA ECONOPACK 30PZ|12.99
Aseo personal|ELA DIARIO ECONOPACK 150PZ|14.99
Aseo personal|ELA PROTECTOR LACTANCIA 40PZ|15.90
Aseo personal|TOALLITAS TUNIES 25PZ|1.99
Aseo personal|TOALLITAS TUNIES 100PZ|4.49
Aseo personal|TOALLITAS TUNIES BEBE 200PZ|7.99
Arreglo estético|BUFANDA LAZO DISEÑO|7.99
Arreglo estético|MECHAS COLORES|2.99
Arreglo estético|PAÑOLETA DISEÑO|3.99
Arreglo estético|PESTAÑAS 5D|4.99
Arreglo estético|UÑAS EAS CAT|6.99
Bisutería|ARETES COLOR DISEÑOS|3.99
Bisutería|ARETES METAL PIEDRAS|4.49
Bisutería|CAJITAS COLETS|4.49
Bisutería|CAPIBARA IMANTADO|2.49
Bisutería|CEPILLO CON ESPEJO|2.99
Bisutería|CEPILLO DE CABELLO ECO|3.99
Bisutería|CEPILLO DE CABELLO PREMIUM|4.49
Bisutería|COLET NEGRO 6PZ|1.49
Bisutería|COLETS X3|2.49
Bisutería|COLETS X4|3.49
Bisutería|COLLAR PENDIENTES|9.90
Bisutería|GANCHITO MUJER 2PZ|3.99
Bisutería|GANCHITOS OJON|1.49
Bisutería|GANCHO COLET 5 PZ|6.49
Bisutería|GANCHO DE METAL|1.49
Bisutería|GANCHO PICO MARIPOSA|1.49
Bisutería|GANCHOS COLETS 4 PZ CUADRADO|5.49
Bisutería|GANCHOS COLETS 4 PZ FLOR|5.49
Bisutería|GANCHOS VARIOS|1.99
Bisutería|HORQUILLAS PARA CABELLO 20PZS|1.49
Bisutería|LIGAS EN TUBO|1.49
Bisutería|LLAVERO LA BUBU|3.49
Bisutería|LLAVERO OSITO NEGRO|1.49
Bisutería|LLAVEROS SURTIDOS|1.49
Bisutería|MINI COLETS 50PZ|2.99
Bisutería|MINI SET NIÑA DISEÑOS|7.49
Bisutería|PALITO CHINO DISEÑO|7.90
Bisutería|PEINE DE CABELLO|1.99
Bisutería|PICO PATO 2 PZ FLOR|4.99
Bisutería|PICO PATO 2 PZ MOÑO|4.49
Bisutería|PILIMINI 3PZ|2.49
Bisutería|PULCERA ACERO|10.90
Bisutería|SET GANCHITOS 12PZ|2.49
Bisutería|VINCHA COLET 2 PZ|4.99
Bisutería|VINCHA DE TELA|2.99
Bisutería|VINCHA MAQUILLAJE 4 PZ|8.99
Bisutería|VINCHA PLASTICA|2.49
Prendas de vestir|MEDIA CARNERO MUJER|5.99
Prendas de vestir|PANTI NIÑA|12.90
Prendas de vestir|MEDIA DE NIÑO ALGODÓN|3.49
Prendas de vestir|MEDIA TOBILLERA ALGODÓN|3.99
Prendas de vestir|MEDIA TALONERA ALGODÓN|2.99
Prendas de vestir|MEDIAS PARA DORMIR 2 A 4|2.49
Prendas de vestir|MEDIAS PARA DORMIR 4 A 6|2.49
Prendas de vestir|MEDIAS PARA DORMIR 6 A 8|2.49
Prendas de vestir|MEDIAS PARA DORMIR ADULTO|3.99
Prendas de vestir|INTERIOR 505549|4.99
Prendas de vestir|INTERIOR WS1202|6.99
Prendas de vestir|INTERIOR 505233|4.99
Prendas de vestir|INTERIOR DH2110|7.99
Prendas de vestir|INTERIOR 8458|8.49
Prendas de vestir|INTERIOR R187|8.49
Prendas de vestir|INTERIOR DK670|6.99
Prendas de vestir|INTERIOR 6048|7.99
Prendas de vestir|INTERIOR HB904|7.99
Prendas de vestir|INTERIOR Q8300|9.90
Prendas de vestir|INTERIOR D28101|8.99
Prendas de vestir|INTERIOR HB3811|7.49
Prendas de vestir|INTERIOR ST2402|6.99
Prendas de vestir|INTERIOR Q8510|9.90
Prendas de vestir|INTERIOR 8290|9.90
Prendas de vestir|INTERIOR DH1965|6.49
Prendas de vestir|INTERIOR X2968|6.49
Prendas de vestir|INTERIOR DH1677|6.99
Prendas de vestir|INTERIOR 6005|6.49
Prendas de vestir|INTERIOR 240126|7.99
Prendas de vestir|INTERIOR DH1192|6.99
Prendas de vestir|INTERIOR CYB704|8.99
Prendas de vestir|INTERIOR 66026|10.90
Prendas de vestir|INTERIOR V2148|9.90
Prendas de vestir|INTERIOR H6004|7.99
Prendas de vestir|2095 BLUZA|19.90
Prendas de vestir|FAJA WF5895|13.90
Prendas de vestir|FAJA 0087|15.90
Prendas de vestir|BOXER 6723|9.90
Prendas de vestir|BOXER X5665|9.90
Prendas de vestir|BOXER ZL51250|7.49
Prendas de vestir|BOXER B017|9.90
Prendas de vestir|SOSTEN BX43536|14.90
Prendas de vestir|SOSTEN 1009|14.49
Prendas de vestir|SOSTEN 33071|10.90
Prendas de vestir|SOSTEN ZB664|11.90
Prendas de vestir|SOSTEN XFS33|14.90
Prendas de vestir|SOSTEN XFS27|12.90
Prendas de vestir|SOSTEN XFS38|12.90
Prendas de vestir|SOSTEN 92531|13.99
Prendas de vestir|SOSTEN XFS22|12.90
Prendas de vestir|SOSTEN XFS29|14.90
Prendas de vestir|SOSTEN WJ2310|13.99
Prendas de vestir|SOSTEN XFS41|15.90
Prendas de vestir|SOSTEN XFS18|15.90
Prendas de vestir|SOSTEN 999|15.90
Prendas de vestir|SOSTEN 8803|13.99
Prendas de vestir|SOSTEN XFS48|13.49
Prendas de vestir|SOSTEN XFS54|13.99
Prendas de vestir|SOSTEN 2012|8.99
Prendas de vestir|SOSTEN 988|14.90
Prendas de vestir|TOP 8723|19.90
Prendas de vestir|TOP 001|12.90
Prendas de vestir|TOP 719|12.90
Prendas de vestir|CHULLOS VARIOS|3.99
Prendas de vestir|PIJAMA NIÑOS|24.90
Prendas de vestir|PIJAMA MUJER|31.90
Prendas de vestir|CASACA UNISEX CORTAVIENTO|29.90
Prendas de vestir|CASACA NIÑO 6666|69.90
Prendas de vestir|CASACA DAMA X012|59.90
Menaje|AGENDA IMANTADA DE LICENCIA|14.90
Menaje|ALCANCIA STICH DE #1|3.99
Menaje|ALCANCIA STICH DE #2|4.99
Menaje|ALCANCIA STICH DE #3|5.99
Menaje|ALCANCIA STICH DE #4|6.99
Menaje|ALCANCIA STICH DE #5|7.99
Menaje|CONDIMENTERO DE LICENCIA|8.99
Menaje|CORTINA DUCHA DE LICENCIA|7.90
Menaje|ESFERA LED|6.99
Menaje|HELADERA KWAI|15.99
Menaje|INDIVIDUALES DE MESA DE LICENCIA|1.49
Menaje|JOYERO 3 NIVELES PVC|5.49
Menaje|JOYERO DE ACRILICO|7.99
Menaje|JOYERO DE GAMUZA|11.90
Menaje|MANTAS IMPORTADAS|34.90
Menaje|MANTAS LUMINOSAS DE LICENCIA|14.90
Menaje|MESA PORTATIL CAMA DE LICENCIA|17.90
Menaje|NECESER CIRCULAR DOMO|10.90
Menaje|RELOJ DE PARED DE LICENCIA|11.90
Menaje|SILLA PLEGABLE DE LICENCIA|14.90
Menaje|TACHO DE LICENCIA|12.90
Menaje|TAZA CALENTADORA|14.90
Menaje|TAZAS ANCHAS DE LICENCIA|7.99
Menaje|TOMATODO COHETE DE LICENCIA|8.99
Artículos personales|BOLSO MARRON GRANDE|16.90
Artículos personales|CARTERA COLORES|15.90
Artículos personales|CARTERA FASHION BAG|24.90
Artículos personales|CARTERA MIN MIN|17.49
Artículos personales|MOCHILA LOVE|11.90
Otros|BOLSA REGALO M|1.99
Otros|BOLSA REGALO MZ|2.99
Otros|BOLSA REGALO L|3.49
Otros|BOLSA REGALO XL|3.99
CATALOG;

        $code = Product::count();
        foreach (explode("\n", trim($additionalCatalog)) as $line) {
            [$categoryName, $name, $salePrice] = explode('|', $line);
            $category = $categories[$categoryName] ??= Category::create(['name' => $categoryName, 'slug' => str($categoryName)->slug()]);
            $product = Product::create(['category_id' => $category->id, 'internal_code' => 'LIU-' . str_pad((string) (++$code), 4, '0', STR_PAD_LEFT), 'name' => $name, 'purchase_price' => 0, 'sale_price' => (float) $salePrice, 'minimum_stock' => 3]);
            $branch = $mujerCategories->contains($categoryName) ? $mujer : $pauza;
            Inventory::create(['product_id' => $product->id, 'branch_id' => $branch->id, 'quantity' => 0]);
        }
    }
}
