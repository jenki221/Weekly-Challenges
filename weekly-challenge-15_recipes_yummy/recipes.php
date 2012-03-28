<?php

$file_in  = "/PTH/TO/239_YummySoup_recipes.ysr";
$file_out = "/PTH/TO/239_YummySoup_recipes_preprocessed.xml";

$xsl = <<<EOB
<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="1.0" 
     xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
     xmlns:php="http://php.net/xsl">

<xsl:output method="xml" encoding="utf-8" indent="yes"/>
<xsl:preserve-space elements="*"/>

<!-- ENTRY POINT -->
<xsl:template match="/">
<plist version="1.0">
<array>
     <xsl:apply-templates select="/plist/array/dict" />
</array>
</plist>
</xsl:template>

<!-- ONCE PER RECIPE -->
<xsl:template match="/plist/array/dict">
    <dict>
        <xsl:variable name="directions"><xsl:value-of select="./string[preceding-sibling::key='directions'][1]"/></xsl:variable>
        <xsl:variable name="ingredients"><xsl:value-of select="./string[preceding-sibling::key='ingredientsArray'][1]"/></xsl:variable>
        <xsl:variable name="yield"><xsl:value-of select="./string[preceding-sibling::key='yield'][1]"/></xsl:variable>
        
        <xsl:copy-of select="php:function('getIngredients', \$ingredients, \$yield )"/>
        <xsl:copy-of select="php:function('splitSteps', \$directions )"/>
        <xsl:copy-of select="./*[preceding-sibling::*[1][not(. = 'ingredientsArray') and not(. = 'firstImage')  and not(. = 'yield') and not(. = 'directions')]]" />
    </dict>
</xsl:template>

</xsl:stylesheet>
EOB;

/**
 * handles the directions, which are split in all sort of weird and wonderful ways, and turns the into a set of nodes. Note that the set of nodes need to ve a valid XML document, i.e. with a single rood node. Lucily, that works here.
 * @param  {String} $node the content of the ndoe
 * @return {DOMDocument}       a DOM tree
 */
function splitSteps( $node ) {
    
    $find    = array();
    $replace = array();
    
    #hteml_decode_entities didn't work 
    array_push( $find, "'&lt;'" );
    array_push( $replace,  "<" );
    
    array_push( $find, "'&gt;'" );
    array_push( $replace,  ">" );
    
    array_push( $find, "'<br>'" );
    array_push( $replace,  "\n\n" );
    
    array_push( $find, "'</?[ou]l>'" );
    array_push( $replace,  "" );
    
    array_push( $find, "'<li>'" );
    array_push( $replace,  "\n" );
    
    array_push( $find, "'</li>'" );
    array_push( $replace,  "\n" );
    
    array_push( $find, "'\n[ \t]+'" );
    array_push( $replace,  "\n" );
    
    array_push( $find, "'\n{2,}'" );
    array_push( $replace,  "\n\n" );
    
    array_push( $find, "'(^|\n)\(?\d+\.?\d?\)\s*'" );
    array_push( $replace,  "\n\n" );
    
    array_push( $find, "'\n\n'" );
    array_push( $replace,  "</step>\n<step>" );
    
    $node = "<directions><step>" . preg_replace( $find, $replace, $node ) . "</step></directions>";
    $node =  preg_replace( "'<step></step?'", "", $node );
    $dom = new DOMDocument("1.0","UTF-8");
    $dom->loadXML( $node ) or die ( $node );
    return $dom;
}



/**
 * parses the ingredient string, which looks like a python tuple, but without quoted string
 * (
 *      (
 *       "For The Pastry:",
 *       "",
 *       "",
 *       "",
 *       YES,
 *       NO,
 *       945989
 *     ),(
 *       125,
 *       g,
 *       "unsalted butter",
 *       "",
 *       NO,
 *       NO,
 *       2364227
 *     ),( ....
 * @param  String $node the node being processed
 * @param  String $serves another node, the ones that gives information on serving
 * @return DOMFragment       a list of nodes
 */
function getIngredients( $node, $serves ) {    
    //removes enclosing ()
    $node = substr( $node, 1, -1 ); 
    
    //gets rid of stuff and flatten
    $node = preg_replace( "'\"'", "", $node );
    $node = preg_replace( "'\n\s*'", " ", $node );
    
    //gets individual  ingredients or group names
    $lines = preg_split( "'(^|\),)\s*\('", $node );
    $header = array(
            "quantity" => 0
          , "measurement" => 1
          , "name"        => 2
          , "preparation" => 3
          , "isgroup"     => 4 
          , "ignore"      => 5
          , "ignore2"     => 6
          );
    
    $dom  = new DOMDocument( "1.0", "UTF-8" );
    $root = $dom->appendChild( new DOMElement( 'ingredients' ) );
    $root->appendChild( new DOMElement( 'serves', $serves ) );
    
    for( $i=0, $i2=sizeof( $lines ); $i<$i2; $i++ ){
        $line = trim( $lines[$i] );
        if( "" === $line ){
            continue;
        }
        //treats line as a CSV
        $fields = preg_split( "':?\,\s*'", $line );
        //a line is either a groupname or an igredient
        if( "YES" === $fields[ $header["isgroup"] ] ){
            $ndGroup = $root->appendChild( new DOMElement( 'group' ) );
            $ndGroup->appendChild( new DOMElement( 'name', $fields[ $header["quantity"] ] ) );
        } else {
            if( !isset( $ndGroup ) ){
                $ndGroup = $root->appendChild( new DOMElement( 'group' ) );
            }
            $ndIngredient = $ndGroup->appendChild( new DOMElement( 'ingredient' ) );
            $ndIngredient->appendChild( new DOMElement( 'quantity',     $fields[ $header["quantity"] ] ) );
            $ndIngredient->appendChild( new DOMElement( 'measurement',  $fields[ $header["measurement"] ] ) );
            $ndIngredient->appendChild( new DOMElement( 'name',         $fields[ $header["name"] ] ) );
            $ndIngredient->appendChild( new DOMElement( 'preparation',  $fields[ $header["preparation"] ] ) );
        }
    }
    return $root;
}

$xml_in  = new DOMDocument;
$xml_in->load( $file_in );
$xsl_doc  = new DOMDocument( "1.0", "utf-8" );
$xsl_doc->loadXML( $xsl );
$xslt = new XSLTProcessor();
$xslt->registerPhpFunctions();
$xslt->importStyleSheet( $xsl_doc );

#NOTE that using <xsl:processing instruction doesn't seem to work 
#the processing instruction is gereated as <?xml version=".." >
#i.e., without the closing ?
$xml_out = $xslt->transformToXML( $xml_in );

$FH_file_out = fopen( $file_out, "w" );
fwrite( $FH_file_out, $xml_out );
fclose( $FH_file_out );

echo 'XML preprocessed';
?>

