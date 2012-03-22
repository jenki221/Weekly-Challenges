#! /usr/bin/env python

import csv
import re
import os

csv.register_dialect('custom',
                     delimiter=',',
                     doublequote=True,
                     escapechar=None,
                     quotechar='"',
                     quoting=csv.QUOTE_MINIMAL,
                     skipinitialspace=False)
TAB = "   "
input_directory  = "/PATH/TO/IN"
output_directory = "/PATH/TO/OUT"

def main( input_dir, output_dir ):
    """Processes all the csv files in a directory, and generates an XML file for each of them.
    
    Keyword arguments:
    input_dir   -- where to get the input csv files from
    output_dir  -- where to save the output xml files to
    
    """
    postfix = "csv"

    for csv_file in os.listdir( input_dir ):
        if not csv_file.lower().endswith( postfix ):
            continue
        with open( os.path.join( input_dir , csv_file ), 'rb' ) as file_obj:
            meta, ings, steps = spreadsheet_read( file_obj, csv_file[:-4] )
            xml_file = re.sub( r'\.%s' % postfix, ".xml", csv_file )
        with open( os.path.join( output_dir , xml_file ) ,'wb' ) as file_obj:
            file_obj.write( recipe_print( meta, ings, steps ) )
    return
    
    
    
    
def spreadsheet_read( spreadsheet_file, filename ):
    """Extract metadata, ingredient list, and directions from a csv file
    
    Keyword arguments:
    spreadsheet_file --  a file handler
    filename         --  the name of the file
    
    return metadata, ingredient list, and directions dictionaries
    
    """
    ingGroup = 0                            #pointer to active ingredient group
    lastIngs = []                           #stack of ingredients to be used in a single step
    meta = { 'title' : filename }           #the meta info, returned
    ings = [ { 'name' : '', 'list' : [] } ] #the ingredient groups, returned
    steps = []                              #the steps, returned
    
    spreadsheet = csv.DictReader(spreadsheet_file, dialect='custom')
        
    #oreoprocesses data
    for row in spreadsheet:
        
        #the Group cell is only filled in when the group change
        #when that happens, a new group is added to list
        if row['Group']:
            ings.append( { 'name' : row['Group'], 'list' : [] } )
            ingGroup += 1
        
        #preparation has no key in the original, so we add one
        cells = {}
        for k, v in row.items():
            k = k or 'preparation'
            cells[k] = v
        
        #collects ingredients
        ing = {}
        if cells['ingredient']:
            #ingredients are listed as 'oil, olive', so we need to swap them around to get "olive oil"
            if re.search( ",", cells['ingredient'] ):
                ing['name'] = re.sub( "^(.+?), (.+)$", r"\2 \1", cells['ingredient'] )
            else:
                ing['name'] = cells['ingredient']
            ing['measurement'] = cells['unit']
            ing['preparation'] = cells['preparation']
            ing['quantity'] = cells['amount']
            ings[ingGroup]['list'].append( ing )
            #some rows contain ingredients but not actions 
            #those ingredients are collected into a list and used for the first action 
            #ingredients are listed as 'oil, olive', so that we can extract just 'oil' easily
            lastIngs.append( re.sub( ',.*$', "", cells['ingredient'] ) )
            
        #collect steps
        step = ""
        if  cells['action']:
            #actions  can contain the string {} to refer to the ingredient - e.g., "add the {} and stir"
            step = re.sub( r'{}', ", ".join( lastIngs ), row['action'] )
            #the rest of the information is quite granular, but we ignore that and just return the one string
            if cells['medium']:
                step += " in"
                if cells['amount medium']:
                    step += " " + cells['amount medium']
                if cells['unit medium']:
                    step += " " + cells['unit medium']
                step += " " + cells['medium']
            if cells['temperature']:
                step += " on " + cells['temperature']
            if cells['ready when']:
                step += " until " + cells['ready when']
            if cells['estimated time']:
                step += ", " + cells['estimated time']
            lastIngs = []
            steps.append( step )
    return meta, ings, steps
    
    
    
    

def recipe_print( meta, ingredients, directions ):
    """Returns an XML document given some metadata, ingredients list, and directions.
    
    Keyword arguments:
    meta        -- a dictionary of meta information, simple key value pair 
    ingredients -- a list of ingredient groups. Each group is dictionary including a name and list of ingredients, and each is a ingredient is itslef a tuple
    directions  -- a list of steps strings
    
    """
    str = """<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<?xml-stylesheet type="text/xsl" href="_assets/recipe.xsl"?>

<recipe lang="en-uk">"""
    str +=  el_CData( "title", meta['title'] )
    str +=  el_CData( "description", "" )
    str +=  el_CData( "source", "" )
    
    str +=  el_parent_open( "cuisine" )
    str +=  el_node( "style", "", 2 )
    str +=  el_node( "region", "", 2 )
    str +=  el_node( "approach", "", 2 )
    str +=  el_parent_close( "cuisine" )
    
    str +=  el_parent_open( "tags" )
    str +=  el_node( "tag", "", 2 )
    str +=  el_parent_close( "tags" )
    
    #steps
    str +=  el_parent_open( "directions" )
    for step in directions:
        if step: 
            str +=  el_CData( "step", step, 2 )
    str +=  el_parent_close( "directions" )
    
    #ingredients
    str +=  el_parent_open( "ingredients" )
    str +=  el_node( "serves", "", 2 )        
    for group in ingredients:
        if group['list']:
            str +=  el_parent_open( "group", 2 )
            if group['name']:
                str +=  el_node( "name", group['name'], 3 )
            for ingredient in group['list']:
                str +=  el_parent_open( "ingredient", 3 )
                str +=  el_node( "quantity", ingredient['quantity'], 4 )
                str +=  el_node( "measurement", ingredient['measurement'], 4 )
                str +=  el_node( "name", ingredient['name'], 4 )
                str +=  el_node( "preparation", ingredient['preparation'], 4 )
                str +=  el_parent_close( "ingredient", 3 )
            str +=  el_parent_close( "group", 2 )        
    str +=  el_parent_close( "ingredients" )
    
    str +=  el_parent_close( "recipe", 0 )
    return str
    

def el_CData( nodeName, str, tabs=1 ):
    """Format nodeName as a CData element with optional tabs and return it."""
    return TAB * tabs + "<"+nodeName+"><![CDATA[" + str + "]]></" + nodeName + ">\n";
    
def el_node( nodeName, str, tabs=1 ):
    """Crete a node nodeName with content str and optional tabs and return it."""
    nodeName = ( nodeName or "preparation" )
    str = ( str or "" )
    return TAB * tabs + "<"+nodeName+">" + str + "</" + nodeName + ">\n";
    
def el_parent_open( nodeName, tabs=1 ):
    """Format nodeName as an opening tag with optional tabs and return it."""
    return TAB * tabs + "<"+nodeName+">\n";
    
def el_parent_close( nodeName, tabs=1 ):
    """Format nodeName as a closing tag with optional tabs and return it."""
    return TAB * tabs + "</"+nodeName+">\n";
    
if __name__ == "__main__":
    main( input_directory, output_directory ) 