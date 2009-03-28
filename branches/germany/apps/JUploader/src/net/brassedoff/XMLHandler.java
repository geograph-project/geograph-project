/*
 * XMLHandler.java
 *
 * Created on 20 August 2006, 20:43
 *
 * To change this template, choose Tools | Template Manager
 * and open the template in the editor.
 */

package net.brassedoff;
import com.softcorporation.xmllight.*;

/**
 *
 * @author david
 */
public class XMLHandler {
    
    /** Creates a new instance of XMLHandler */
    public XMLHandler() {
    }

    public static String getXMLField(String xmlData, String elementName) {
        Element rootElement;
        String rtnData;
        
        try {
            rootElement = XMLLight.getElem(xmlData);
            rtnData = rootElement.getElem(elementName).getCont();
        } catch (Exception ex) {
            rtnData = new String("");
        }
        
        return rtnData;
    }
    
}
