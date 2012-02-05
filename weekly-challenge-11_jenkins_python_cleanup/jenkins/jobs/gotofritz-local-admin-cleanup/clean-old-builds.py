#!/usr/bin/env python
import argparse
import datetime
import os
import shutil
import pymysql

def main():
    #sets up command
    parser = argparse.ArgumentParser(description="deletes builds from a server. Builds include databases and folders.")
    parser.add_argument( "buildlist",
                    action="store",
                    help="buildlist file")
    parser.add_argument( "-b", "--buildsToKeep",
                    action="store", default=5, type=int, 
                    help="how many builds to keep (default 5)")
    parser.add_argument( "-r", "--rootFolder", 
                    action="store", type=str, required=True, nargs="*",
                    help="Where is the webroot with the builds folders")
    parser.add_argument( "-d", "--databases", 
                    action="store", type=str, required=True, nargs="*",
                    help="databases")
    parser.add_argument( "-u", "--user", 
                    action="store", type=str, default="root",
                    help="database user")
    parser.add_argument( "-p", "--password", 
                    action="store", type=str, default="password",
                    help="database password")
    args = parser.parse_args()
    buildsToKeep = args.__dict__['buildsToKeep']
    folders = map( slash, args.__dict__['rootFolder'] )
    databases = args.__dict__['databases'][:]

    #collects build info, removing earliest duplicates
    builds = []
    with open( args.__dict__['buildlist'], 'r') as f:
        for build in f:
            build = build.rstrip()
            if build in builds:
                builds.remove( build )
            builds.append( build )
    f.closed

    #esxit early if nothing to do
    if len(builds) <= buildsToKeep:
        print "No builds to delete"
        exit();

    #protection from silly mistakes
    if 1 >= buildsToKeep:
        print "Must keep at least 2 builds"
        exit();
    
    #creates two lists, one per job
    builds_deleted = builds[0:-buildsToKeep]
    builds_kept = builds[-buildsToKeep:]

    #db connections
    conn = pymysql.connect(host='localhost', port=3306, user=args.__dict__['user'], passwd=args.__dict__['password'] )
    cur = conn.cursor()

    #keeps folders if they exist,
    #it replaces those that don't with entries from the delete list
    for build in builds_kept:
        for folder in folders:
            folder += build
            if os.path.exists( folder ):
                print "keep %(b)s" % { 'b' : folder }
            else:
                if 0 < len( builds_deleted ):
                    builds_kept.append( builds_deleted.pop()  )
                print "couldn't keep %(b)s" % { 'b' : folder }
    
    #delets folders and databases
    for build in builds_deleted:
        for folder in folders:
            folder += build
            if os.path.exists( folder ):
                print "delete %(b)s" % { 'b' : folder }
                shutil.rmtree( folder )
            else:
                print "couldn't delete %(b)s" % { 'b' : folder }
        for database in databases:
            database += build
            cur.execute( "DROP DATABASE IF EXISTS `%(b)s`;"  % { 'b' : database } )
            print "delete %(b)s" % { 'b' : database }
    
    cur.close()
    conn.close()

    #updates buildlist
    with open( args.__dict__['buildlist'], 'w') as f:
        for build in builds_kept:
            f.write( build + "\n" )
    f.closed
    print "FINISHED"

#normalizes slashes
def slash( s ): return s.rstrip( '/' ) + '/'
 
if __name__ == "__main__":
    main()
