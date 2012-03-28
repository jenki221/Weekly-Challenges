<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="1.0"
    xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
    xmlns:redirect="http://xml.apache.org/xalan/redirect"
    extension-element-prefixes="redirect java"
    xmlns:lxslt="http://xml.apache.org/xslt"
    xmlns:xalan="http://xml.apache.org/xalan"
    exclude-result-prefixes="xalan java"
    xmlns:java="http://xml.apache.org/xslt/java">
<xsl:output 
indent = "no"
cdata-section-elements="title description source step name preparation"/>
  
<xsl:template match="/">
  <xsl:copy>
    <xsl:apply-templates select="/plist/array/dict"/>
  </xsl:copy>
</xsl:template>



<xsl:template name="val">
    <xsl:param name="node" />
    <xsl:value-of select="$node/following-sibling::string[1]" />
</xsl:template>

<xsl:template name="splitstring">
    <xsl:param name="list" /> 
    <xsl:param name="delimiter" select="','"/> 
    <xsl:param name="tag" select="'tag'"/> 
    <xsl:variable name="newstring"><xsl:choose>
        <xsl:when test="contains( $list, $delimiter )"><xsl:value-of select="normalize-space($list)" /></xsl:when>
        <xsl:otherwise><xsl:value-of select="concat(normalize-space($list), $delimiter)" /></xsl:otherwise>
    </xsl:choose></xsl:variable>
    <xsl:variable name="first" select="substring-before($newstring, $delimiter)" /> 
    <xsl:variable name="remaining" select="substring-after($newstring, $delimiter)" />
    <xsl:element name="{$tag}"><xsl:value-of select="$first" /></xsl:element>
        <xsl:if test="$remaining">
            <xsl:call-template name="splitstring">
            <xsl:with-param name="strlisting" select="$remaining" /> 
            <xsl:with-param name="delimiter" select="$delimiter" /> 
            <xsl:with-param name="tag" select="$tag" /> 
        </xsl:call-template>
    </xsl:if>
</xsl:template>
        
<xsl:template match="/plist/array/dict" xmlns:java="http://xml.apache.org/xslt/java">
    <xsl:variable name="cuisine"><xsl:call-template name="val"><xsl:with-param name="node" select="*[. = 'cuisine']"/></xsl:call-template></xsl:variable>
    <xsl:variable name="tags"><xsl:call-template name="val"><xsl:with-param name="node" select="*[. = 'keywords']"/></xsl:call-template></xsl:variable>
    <xsl:variable name="title"><xsl:call-template name="val"><xsl:with-param name="node" select="*[. = 'name']"/></xsl:call-template></xsl:variable>

    <redirect:write  file="/PTH/TO/OUTPUT/{$title}.xml">
<xsl:processing-instruction name="xml-stylesheet">
href="_assets/recipe.xsl" type="text/xsl"
</xsl:processing-instruction>

<recipe lang="en-uk">
    <title><xsl:value-of select="$title" /></title>  
    <description><xsl:call-template name="val"><xsl:with-param name="node" select="*[. = 'recipeDescription']"/></xsl:call-template></description>   
    <source><xsl:call-template name="val"><xsl:with-param name="node" select="*[. = 'attribution']"/></xsl:call-template></source>
    <img><xsl:value-of select="*[. = 'firstImage']/following-sibling::data[1]" /></img>
    <cuisine>
        <xsl:choose>
            <xsl:when test="contains( $cuisine, '/')">
                <style><xsl:value-of select="normalize-space( substring-before( $cuisine, '/' ) )" /></style>
                <region><xsl:value-of select="normalize-space( substring-after( $cuisine, '/') )" /></region>
            </xsl:when>
            <xsl:otherwise>
                <style><xsl:value-of select="$cuisine" /></style>
                <region></region>
            </xsl:otherwise>
        </xsl:choose>
        <approach></approach>
    </cuisine>
    <tags>
        <xsl:call-template name="splitstring"><xsl:with-param name="list" select="$tags"/></xsl:call-template>
    </tags>
    <xsl:copy-of select="directions" />
    <xsl:copy-of select="ingredients" />
</recipe>
      </redirect:write>

</xsl:template>
</xsl:stylesheet>