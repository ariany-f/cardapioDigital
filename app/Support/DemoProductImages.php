<?php

namespace App\Support;

/**
 * Imagens locais em public/images/demo/products/ (versionadas no repo).
 */
class DemoProductImages
{
    public static function pathFor(string $productName): ?string
    {
        return match ($productName) {
            'X-Burger ACME' => 'images/demo/products/x-burger-acme.jpg',
            'X-Bacon' => 'images/demo/products/x-bacon.jpg',
            'X-Tudo' => 'images/demo/products/x-tudo.jpg',
            'X-Salada' => 'images/demo/products/x-salada.jpg',
            'Cheeseburger' => 'images/demo/products/cheeseburger.jpg',
            'Veggie Burger' => 'images/demo/products/veggie-burger.jpg',
            'Batata frita' => 'images/demo/products/batata-frita.jpg',
            'Onion rings' => 'images/demo/products/onion-rings.jpg',
            'Nuggets (8 un.)' => 'images/demo/products/nuggets.jpg',
            'Frango a passarinho' => 'images/demo/products/frango-passarinho.jpg',
            'Refrigerante lata' => 'images/demo/products/refrigerante-lata.jpg',
            'Suco natural' => 'images/demo/products/suco-natural.jpg',
            'Água mineral' => 'images/demo/products/agua-mineral.jpg',
            'Milkshake' => 'images/demo/products/milkshake.jpg',
            'Brownie com sorvete' => 'images/demo/products/brownie.jpg',
            'Petit gateau' => 'images/demo/products/petit-gateau.jpg',
            'Mousse de maracujá' => 'images/demo/products/mousse-maracuja.jpg',
            'Combo Executivo' => 'images/demo/products/combo-executivo.jpg',
            'Combo Família' => 'images/demo/products/combo-familia.jpg',
            'Combo Kids' => 'images/demo/products/combo-kids.jpg',
            'Combo Clássico' => 'images/demo/products/combo-executivo.jpg',
            default => null,
        };
    }
}
