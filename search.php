<?php

    /**
     * @author: Tim Vervoort
     */

    include("simple_html_dom.php"); // Library to parse HTML DOM

    /**
     * Retreives a HTML file from the webshops and extracts the product items (if any).
     * Returns a list of objects (name {string}, price {float}, link {string} and image {string}) or the empty list of nothing matched the search criteria.
     * @param $roothURL {string} - The domainname for the webshop.
     * @param $baseURL {string} - The search endpoint (must be publicy accessible) which is to be crawled.
     * @param $search {string} - The search string to be passed to the search endpoint (must be URL encoded).
     * @param $productPath {string} - The DOM selector for a product item.
     * @param $namePath {string} - The DOM selector for a product name.
     * @param $namePathIndex {integer} - The index of the product name DOM selector.
     * @param $pricePath {string} - The DOM selector for the product price.
     * @param $pricePathIndex {integer} - The index of the product price DOM selector.
     */
    function getProducts($rootURL, $baseURL, $search, $productPath, $namePath, $namePathIndex, $pricePath, $pricePathIndex) {

        $url = $baseURL.$search; 
        $html = file_get_html($url);
    
        $products = array();
        foreach($html->find($productPath) as $p) {  

            // Check if this product has all required fields    
            if (count($p->find("img")) >= 1 && count($p->find($namePath)) > $namePathIndex && count($p->find($pricePath)) > $pricePathIndex) {
                
                $o = new stdClass();

                // Product image
                $o->img = "";
                foreach ($p->find("img") as $i) {
                    if (!empty($i->src) && substr($i->src, 0, 5) !== "data:") {
                        $o->img = trim($i->src);
                    }
                    else if (!empty($i->attr['data-src']) && substr($i->attr['data-src'], 0, 5) !== "data:") {
                        $o->img = trim($i->attr['data-src']);
                    }
                }
                if (substr($o->img, 0, 2) === "//") {
                    $o->img = "https:".$o->img;
                }

                $o->name = trim(preg_replace("!\s+!", " ", $p->find($namePath)[$namePathIndex]->plaintext)); // Find the product name

                // Find and convert product price to float
                $o->priceRAW = trim(str_replace(" ", "", str_replace(",", ".", str_replace("&nbsp;", "", $p->find($pricePath)[$pricePathIndex]->plaintext))));
                $o->priceVAL = str_replace(".-", "", str_replace("&euro", "", str_replace("€", "", str_replace("$", "", $o->priceRAW))));
                $o->priceVAL = preg_replace("/\./", "", $o->priceVAL, (substr_count($o->priceVAL, ".") - 1)); // Remove thousand
                $o->price = floatval($o->priceVAL);
               
                // Product link
                $o->link = "";
                foreach ($p->find("a") as $a) {
                    if (!empty($a->href) && $a->href !== "#") {
                        $o->link = trim($a->href);
                    }
                }
                
                if (strpos($o->link, "http://") === false && strpos($o->link, "https://") === false) {
                    $o->link = $rootURL.$o->link;
                }

                array_push($products, $o);

            }       
        }
    
        return $products;
    }

    /**
     * Search a webshop for products meeting the search criteria.
     * Returns a list of objects (name {string}, price {float}, link {string} and image {string}) or the empty list of nothing matched the search criteria.
     * Returns an empty list of the webshop doesn't exist in the function.
     * @param $channel {string} - The name of the webshop*.
     * @param $search {string} - The search query.
     * *The following webshops are supported: fototools, fotokonijnenberg, selexion, mediamarkt, amazon, avned, avblackmagic, bhphotovideo, coolblue.
     */
    function search($channel, $search) {

        $search = str_replace(" ", "+", $search); // URL encode

        if (strtolower($channel) === "fototools") {
            return getProducts("https://fototools.be", "https://fototools.be/index.php?action=search&lang=NL&srchval=", $search, "tr", "td", 1, "td", 2);
        }

        else if (strtolower($channel) === "fotokonijnenberg") {
            return getProducts("https://www.fotokonijnenberg.be", "https://www.fotokonijnenberg.be/catalogsearch/result/?q=", $search, "div.category-products ul li", "div.product-name", 0, "span.price", 0);
        }

        else if (strtolower($channel) === "selexion") {
            return getProducts("https://www.selexion.be", "https://www.selexion.be/nl/search/?text=", $search, "div.product-layout", "div.name", 0, "div.price", 0);
        }

        else if (strtolower($channel) === "mediamarkt") {
            return getProducts("https://www.mediamarkt.be", "https://www.mediamarkt.be/nl/search.html?query=", $search, "ul.products-list li", "h2", 0, "div.price", 0);
        }

        else if (strtolower($channel) === "amazon") {
            return getProducts("https://www.amazon.de", "https://www.amazon.de/s?k=", $search, ".s-result-item", "h2", 0, "span.a-price-whole", 0);
        }

        else if (strtolower($channel) === "avned") {
            return getProducts("http://www.avned.nl", "http://www.avned.nl/catalogsearch/result/?q=", $search, "li.item", "h2.product-name", 0, "span.price", 0);
        }

        else if (strtolower($channel) === "avblackmagic") {
            return getProducts("http://www.avblackmagic.nl", "http://www.avblackmagic.nl/catalogsearch/result/?q=", $search, "li.item", "h2.product-name", 0, "span.price", 0);
        }

        else if (strtolower($channel) === "bhphotovideo") {
            return getProducts("https://www.bhphotovideo.com", "https://www.bhphotovideo.com/c/search?Ntt=", $search, "div.item", "h5", 0, "span.itc-you-pay-price", 0);
        }

        else if (strtolower($channel) === "coolblue") {
            return getProducts("https://www.coolblue.be", "https://www.coolblue.be/nl/zoeken?query=", $search, "div.product", "a.product__title", 0, "span.sales-price", 0);
        }

        /*else if (strtolower($channel) === "barndoor") {
            return getProducts("https://www.filmandvideolighting.com", "https://www.filmandvideolighting.com/search-results.html?query=", $search, "div.item-box", "h2.name", 0, "div.sale-price", 0);
        }*/  
        
        else if (strtolower($channel) === "digistore") {
            return getProducts("https://digistore.eu", "https://digistore.eu/catalogsearch/result/?q=", $search, "li.item", "h2.product-name", 0, "span.price", 0);
        }

        return array();
    }

    if ($argc < 3) {
        exit("Usage: ".$argv[0]." webshop search");
    }

    print_r(search($argv[1], $argv[2]));

?>
