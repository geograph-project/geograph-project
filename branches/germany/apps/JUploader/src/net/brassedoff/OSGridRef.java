/*
 * OSGridRef.java
 *
 * Created on 27 June 2007, 20:54
 *
 * Support for a multitude of grid reference-related functions including
 * calculation of a direction between a pair of correctly formatted
 * grid references.
 *
 */

package net.brassedoff;

import com.sun.org.apache.bcel.internal.verifier.statics.DOUBLE_Upper;
import java.text.ParseException;
import java.util.regex.Pattern;

/**
 *
 * @author david
 */
public class OSGridRef {
  
    // How the squares are named in the UK grid reference
    
    static String [] [] gridMaster = 
        {
            { "SV", "SW", "SX", "SY", "SZ", "TV", "TW"},
            { "SQ", "SR", "SS", "ST", "SU", "TQ", "TR"},
            { "SL", "SM", "SN", "SO", "SP", "TL", "TM"},
            { "SF", "SG", "SH", "SJ", "SK", "TF", "TG"},
            { "SA", "SB", "SC", "SD", "SE", "TA", "TB"},
            { "NV", "NW", "NX", "NY", "NZ", "OV", "OW"},
            { "NQ", "NR", "NS", "NT", "NU", "OQ", "OR"},
            { "NL", "NM", "NN", "NO", "NP", "OL", "OM"},
            { "NF", "NG", "NH", "NJ", "NK", "OF", "OG"},
            { "NA", "NB", "NC", "ND", "NE", "OA", "OB"},
            { "HV", "HW", "HX", "HY", "HZ", "JV", "JW"},
            { "HQ", "HR", "HS", "HT", "NU", "JQ", "JR"},
            { "HL", "HM", "HN", "HO", "HP", "JL", "JM"}
    };
        
    
    /** Creates a new instance of OSGridRef */
    public OSGridRef() {
    }
    
    public static int calculateBearing(String startPoint, String endPoint) throws ParseException {
        
        // both gridrefs must match xxnnnn, xxnnnnnn or xxnnnnnnnnnn before
        // we can attempt a match
        
        String [] validGridRefs = {
            "^[A-Za-z]{2}\\d{4}$",
            "^[A-Za-z]{2}\\d{6}$",
            "^[A-Za-z]{2}\\d{8}$",
            "^[A-Za-z]{2}\\d{10}$",        
        };
        
        int gridStartEast = -1;
        int gridStartNorth = -1;
        int gridEndEast = -1;
        int gridEndNorth = -1;
        boolean startMatch = false;
        boolean endMatch = false;
        String startRef = "";
        String endRef = "";
        int bearing = 0;
        
        
        // I originally overlooked this... the array is all in upper case so we'd better
        // make sure we allow grid ref squares passed in lower case
        
        startPoint = startPoint.toUpperCase();
        endPoint = endPoint.toUpperCase();
        
        for (int i = 0; i < validGridRefs.length; i++) {
            if (Pattern.matches(validGridRefs[i], startPoint)) {
                if (startMatch) {
                    throw new ParseException("Invalid grid reference : duplicate match : " + startPoint, 0);
                }
                startMatch = true;
                startRef = startPoint.substring(2, 4) + startPoint.substring(i + 4, i + 6);
            }
            
            if (Pattern.matches(validGridRefs[i], endPoint)) {
                if (endMatch) {
                    throw new ParseException("Invalid grid reference : duplicate match : " + endPoint, 0);
                }
                endMatch = true;
                endRef = endPoint.substring(2, 4) + endPoint.substring(i + 4, i + 6);
            }
        }

        if (!startMatch || !endMatch) {
            throw new ParseException("Couldn't work out either '" + startPoint + "' or '" + endPoint + "'", 0);
        }
        
        // that gives us start and end points based on four digit references
        // we now need to work out a reference point for the grid square
            
        for (int east = 0; east < gridMaster[0].length; east++) {
            for (int north = 0; north < gridMaster.length; north++) {
                if (gridMaster[north][east].equals(startPoint.substring(0, 2))) {
                    gridStartEast = east;
                    gridStartNorth = north;
                }
                
                if (gridMaster[north][east].equals(endPoint.substring(0, 2))) {
                    gridEndEast = east;
                    gridEndNorth = north;
                }
            }
        }

        if ((gridStartEast == -1) || (gridEndEast == -1)) {
            throw new ParseException("Can't find grid square", 0);
        }
        
        // we can create two fixed length grid positions with coordinates that mean something
        // a bit of simple trig will give us a bearing then
        
        // System.out.println("Grid start = " + gridStartEast + ":" + gridStartNorth + ":" + startRef);
        String fullStartEastStr = Integer.toString(gridStartEast) + startRef.substring(0, 2);
        String fullStartNorthStr = Integer.toString(gridStartNorth) + startRef.substring(2, 4);
        String fullEndEastStr = Integer.toString(gridEndEast) + endRef.substring(0, 2);
        String fullEndNorthStr = Integer.toString(gridEndNorth) + endRef.substring(2, 4);
        
        int fullStartEast = Integer.parseInt(fullStartEastStr);
        int fullStartNorth = Integer.parseInt(fullStartNorthStr);
        int fullEndEast = Integer.parseInt(fullEndEastStr);
        int fullEndNorth = Integer.parseInt(fullEndNorthStr);
        
        int northDelta = fullEndNorth - fullStartNorth;
        int eastDelta = fullEndEast - fullStartEast;
        
        if (Debug.ON) {
            System.out.println("Start position=" + startPoint + ", end position=" + endPoint);
            System.out.println("East delta = " + eastDelta + ", north delta = " + northDelta);
        }
        
        double newAngle = Math.toDegrees(Math.atan2(eastDelta, northDelta));
        if (newAngle < 0.0) {
            newAngle = 360.0 + newAngle;
        }

        if (Debug.ON) {
            System.out.println("Calculated bearing = " + newAngle);
            System.out.println("-----------------------------------------------\n");
        }
        
        return (int) newAngle;
    }
    
    public static int bearingToIndex(double bearing) {
        
        double [] indexList = {-11., 11., 33., 56., 78., 101., 123., 146., 168., 191., 213., 236., 258., 281., 303., 326., 348. };
        
        bearing = bearing % 360.;
        
        if (bearing < 0.0) {
            System.out.println("bearingToINdex got a bearing less than 0. This shouldn't happen");
            return 0;
        }
        
        // little adjustment for those bearings pointing straight up!
        
        if (bearing > 348.) {
            bearing -= 360.;
        }
        int bearingIndex = 0;
        
        for (int i = 0; i < indexList.length - 1; i++) {
            if ((bearing >= indexList[i]) && (bearing < indexList[i + 1])) {
                bearingIndex = i;
                break;
            }
        }
        
        if (Debug.ON) {
            System.out.println("Called with bearing = " + bearing + ", returning index = " + bearingIndex);
        }
        
        return bearingIndex;
        
    }
    
}
