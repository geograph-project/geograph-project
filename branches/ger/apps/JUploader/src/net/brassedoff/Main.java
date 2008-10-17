/*
 * Main.java
 *
 * Created on 18 August 2006, 20:35
 *
 * To change this template, choose Tools | Template Manager
 * and open the template in the editor.
 */

package net.brassedoff;

import java.awt.Toolkit;
import java.io.BufferedReader;
import java.io.FileInputStream;
import java.io.FileNotFoundException;
import java.io.FileReader;
import java.io.IOException;
import java.util.Properties;
import javax.swing.JOptionPane;
import javax.swing.UIManager;
//import com.jgoodies.looks.*;

/**
 *
 * @author david
 */
public class Main {
    
    static String geoURL = new String("http://geograph/juploader.php");
    
    static String juppyVersion = "1.8";
    
    static String [] imageClassList;
    static boolean noCache = true;
    static int geoUserid = 0;
    static boolean doResize = false;
    static String cacheDirectory = "";
    static int tuneParam1;
    static int tuneParam2;
    static String validationToken = "";
    static boolean gridrefFromImage;
    static String lastDirectory = "";

    // this is the list of valid grid ref regexps we support, basically
    // UK 4, 6 and 8 character and Irish 4, 6 and 8.
    
    static String[] validGridRefs = {
        "^[A-Za-z]{2}\\d{4}$",
        "^[A-Za-z]{2}\\d{6}$",
        "^[A-Za-z]{2}\\d{8}$",
        "^[A-Za-z]{2}\\d{10}$",        
        "^[A-Za-z]{1}\\d{4}$",
        "^[A-Za-z]{1}\\d{6}$",
        "^[A-Za-z]{1}\\d{8}$",
        "^[A-Za-z]{1}\\d{10}$"        
    };

    
    /** Creates a new instance of Main */
    public Main() {
    }
    
    /**
     * @param args the command line arguments
     */
    public static void main(String[] args) {
        
        if (Debug.ON) {

            try {
                OSGridRef.calculateBearing("SK0000", "SK0002");
                OSGridRef.calculateBearing("SK0002", "SK0000");
                OSGridRef.calculateBearing("SK0000", "SK0200");
                OSGridRef.calculateBearing("SK0200", "SK0000");
                OSGridRef.calculateBearing("SK0000", "SK0101");
                OSGridRef.calculateBearing("SK0101", "SK0000");
                
                OSGridRef.bearingToIndex(0.);
                OSGridRef.bearingToIndex(12.);
                OSGridRef.bearingToIndex(349.);
                OSGridRef.bearingToIndex(347.);
                OSGridRef.bearingToIndex(359.);
                OSGridRef.bearingToIndex(361.);

            } catch (Exception ex) {

            }
        }
        
        try {
            UIManager.setLookAndFeel("com.jgoodies.looks.plastic.Plastic3DLookAndFeel");
        } catch (Exception e) {
            System.out.println("Unable to set a look and feel - using default");

        }
        
        // check that we've got the correct number of command line arguments
        
        if (args.length != 1) {
            System.out.println("Invalid command line arguments");
            System.exit(1);
        }
        
        geoURL = "http://" + args[0] + "/juploader.php";
        
        // Initialise application with data from server, esp. class list        
                
        LoadCache();
        
        // load properties
        
        Properties propList = new Properties();

        try {
            propList.load(new FileInputStream("juppy.prop"));
            
            // we must have got a good property file read, so...
            
            if (propList.getProperty("doresize").equals("true")) {
                doResize = true;
            } else {
                doResize = false;
            }
            
            cacheDirectory = propList.getProperty("cachedirectory");
            
            if (propList.getProperty("gridrefFromImage", "true").equals("true")) {
                gridrefFromImage = true;
            } else {
                gridrefFromImage = false;
            }
            
        } catch (FileNotFoundException fe) {
            System.out.println("No properties file found");
            
            // we'd better set some defaults
            
            doResize = false;
            cacheDirectory = "";
            
        } catch (IOException io) {
            System.out.println("IO exception reading properties");
        }
        
        // TODO Get user authentication information (login) and validate on server
        
        UploadManager ul = new UploadManager();

        ul.setVisible(true);        
        
        
        
    }
    
    private static void LoadCache() {
        
        // load all the static stuff from the cache
        // warn if there's no cache file and make sure the rest of the
        // app knows there's stuff missing
        
        BufferedReader inp;
        try {
            inp = new BufferedReader(new FileReader("juppycache.xml"));
        } catch (Exception ex) {
            JOptionPane.showMessageDialog(null, "No cache present\nYou will need to log in to\n" +
                    "Geograph before you can use JUppy");
            Toolkit.getDefaultToolkit().beep();
            return;
        }
        
        StringBuffer cacheData = new StringBuffer(5000);
        
        try {
            String cacheLine;
            do {
                cacheLine = inp.readLine();
                if (cacheLine != null) {
                    cacheData.append(cacheLine);
                }
            } while (cacheLine != null);
            
        } catch (Exception ex) {
            JOptionPane.showMessageDialog(null, "No cache present\nYou will need to log in to\n" +
                    "Geograph before you can use JUppy");
            Toolkit.getDefaultToolkit().beep();
            return;            
        }
        
                
        imageClassList = XMLHandler.getXMLField(cacheData.toString(), "classlist").split("}");
        Main.noCache = false;
    }
    
}
