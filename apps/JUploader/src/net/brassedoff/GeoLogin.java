/*
 * GeoLogin.java
 *
 * Created on 20 August 2006, 16:19
 */

package net.brassedoff;

import java.awt.BorderLayout;
import java.awt.Graphics;
import java.awt.Graphics2D;
import java.awt.Image;
import java.awt.Paint;
import java.awt.Toolkit;
import java.awt.event.ActionEvent;
import java.awt.event.ActionListener;
import java.io.BufferedWriter;
import java.io.FileWriter;
import javax.swing.ImageIcon;
import javax.swing.JOptionPane;
import javax.swing.JPanel;
import org.apache.commons.httpclient.HttpClient;
import org.apache.commons.httpclient.methods.GetMethod;

/**
 *
 * @author  david
 */


public class GeoLogin extends javax.swing.JDialog implements ActionListener{
    
    
    private ImagePanel imagePanel;
    Image loginImage;
    
    /** Creates new form GeoLogin */
    public GeoLogin(java.awt.Frame parent, boolean modal) {
        super(parent, modal);
        initComponents();
        
        btnLogin.addActionListener(this);
        btnCancel.addActionListener(this);
        
        // we've got an empty panel in the dialog that we'll
        // drop another panel in that will contain the geograph project logo
        
        imagePanel = new ImagePanel();
        pImageLoc.add(imagePanel, BorderLayout.CENTER);
        
        ImageIcon icon = new ImageIcon(getClass().getResource("/loginpanel.png"));
        loginImage = icon.getImage();
    }
    
    
    
    private class ImagePanel extends JPanel  {
        
        ImagePanel() {
            super();
        }
        
        public void paint(Graphics g) {
            Graphics2D g2d = (Graphics2D) g;
            
            Paint store = g2d.getPaint();
            
            //TODO: Need an image observer here
            
            g.drawImage(loginImage, 0, 0, this);
            
            g2d.setPaint(store);
            
        }
        
        
    }
    
    
    
    public void actionPerformed(ActionEvent ae) {
        String action = ae.getActionCommand();
        
        if (action.equals("Cancel")) {
            
            // user wants out
            
            this.dispose();
            return;
        } else if (action.equals("Login")) {
            
            // we can check the username and password on the server now...
            
            CheckLoginDetails();
            this.dispose();
            
        }
    }
    
    public void CheckLoginDetails() {
        
        HttpClient htc = new HttpClient();
        String xmlResponse = new String();
        String status;
        
        String user = new String(txtUser.getText().trim());
        String password = new String(txtPassword.getPassword());
        
        if (user.equals("") || password.equals("")) {
            JOptionPane.showMessageDialog(this, "Username and / or password not specified");
            Toolkit.getDefaultToolkit().beep();
            return;
        }
        
        GetMethod methodAuthenticate = new GetMethod(Main.geoURL + "?action=login&username=" + user + "&password=" + password);
        try {
            htc.executeMethod(methodAuthenticate);
            byte [] response = methodAuthenticate.getResponseBody();
            xmlResponse = new String(response);
        } catch (Exception ex) {
            System.out.println(ex.getMessage());
            JOptionPane.showMessageDialog(this, "Unable to log in to geograph");
            Toolkit.getDefaultToolkit().beep();
            return;
        }
        
        status = XMLHandler.getXMLField(xmlResponse, "status");
        if (!status.equals("OK")) {
            JOptionPane.showMessageDialog(this, "Geograph returned an error:\n" + status);
            Toolkit.getDefaultToolkit().beep();
            return;
        }
        
        // remember the validation token from the server
        
        Main.validationToken = XMLHandler.getXMLField(xmlResponse, "validation");
        
        // we'll need to store the geo userid because it's used in all sorts of places for creating
        // user classes which the server side will need to simulate
        
        try {
            Main.geoUserid = Integer.parseInt(XMLHandler.getXMLField(xmlResponse, "user_id"));
        } catch (Exception ex) {
            System.out.println(ex.getMessage());
            JOptionPane.showMessageDialog(this, "Invalid integer userid received");
            Toolkit.getDefaultToolkit().beep();
            return;
        }
        
        // If we've got this far, we can be assured that the user is OK and we have a valid
        // numeric user number back from the server
        
        JOptionPane.showMessageDialog(this, "Welcome to Geograph, " +
                XMLHandler.getXMLField(xmlResponse, "realname") + "\n(User number " +
                XMLHandler.getXMLField(xmlResponse, "user_id") + ")\n" +
                "Half a mo whilst I update the image class list");
        
        // load data and initialise the cache file
        
        GetMethod methodGetClass = new GetMethod(Main.geoURL + "?action=getclass");
        
        try {
            htc.executeMethod(methodGetClass);
            byte [] response = methodGetClass.getResponseBody();
            xmlResponse = new String(response);
        } catch (Exception ex) {
            System.out.println(ex.getMessage());
            JOptionPane.showMessageDialog(this, "Unable to fetch image class list");
            Toolkit.getDefaultToolkit().beep();
        }
        
        status = XMLHandler.getXMLField(xmlResponse, "status");
        System.out.println("status=" + status);
        if (!status.equals("OK")) {
            Toolkit.getDefaultToolkit().beep();
            JOptionPane.showMessageDialog(null, "Couldn't get response from server\nUnable to initialise");
            System.exit(1);
        }
        
        BufferedWriter op;
        
        try {
            op = new BufferedWriter(new FileWriter("juppycache.xml"));
        } catch (Exception ex) {
            JOptionPane.showMessageDialog(this, "Unable to create juppycache.xml");
            Toolkit.getDefaultToolkit().beep();
            return;
        }
        
        // output what we've just got to the cache
        
        try {
            op.write("<juppycache>\n");
            op.write("<classlist>\n");
            op.write(XMLHandler.getXMLField(xmlResponse, "classlist"));
            op.write("</classlist>\n");
            op.write("</juppycache>\n");
            op.close();
        } catch (Exception ex) {
            JOptionPane.showMessageDialog(this, "Error writing cache file - could be corrupted\n" +
                    "Please delete juppycache.xml and re-run");
            Toolkit.getDefaultToolkit().beep();
            return;
        }
        
        Main.imageClassList = XMLHandler.getXMLField(xmlResponse, "classlist").split("}");
        Main.noCache = false;
        // enable the items menu
        
        
    }
    
    
    /** This method is called from within the constructor to
     * initialize the form.
     * WARNING: Do NOT modify this code. The content of this method is
     * always regenerated by the Form Editor.
     */
    // <editor-fold defaultstate="collapsed" desc=" Generated Code ">//GEN-BEGIN:initComponents
    private void initComponents() {
        jLabel1 = new javax.swing.JLabel();
        jLabel2 = new javax.swing.JLabel();
        txtUser = new javax.swing.JTextField();
        txtPassword = new javax.swing.JPasswordField();
        pImageLoc = new javax.swing.JPanel();
        btnLogin = new javax.swing.JButton();
        btnCancel = new javax.swing.JButton();

        setDefaultCloseOperation(javax.swing.WindowConstants.DISPOSE_ON_CLOSE);
        setResizable(false);
        jLabel1.setText("Geograph user name:");

        jLabel2.setText("Geograph password:");

        pImageLoc.setLayout(new java.awt.BorderLayout());

        btnLogin.setText("Login");

        btnCancel.setText("Cancel");

        org.jdesktop.layout.GroupLayout layout = new org.jdesktop.layout.GroupLayout(getContentPane());
        getContentPane().setLayout(layout);
        layout.setHorizontalGroup(
            layout.createParallelGroup(org.jdesktop.layout.GroupLayout.LEADING)
            .add(layout.createSequentialGroup()
                .addContainerGap()
                .add(layout.createParallelGroup(org.jdesktop.layout.GroupLayout.LEADING)
                    .add(layout.createSequentialGroup()
                        .add(layout.createParallelGroup(org.jdesktop.layout.GroupLayout.TRAILING)
                            .add(jLabel2)
                            .add(jLabel1))
                        .addPreferredGap(org.jdesktop.layout.LayoutStyle.RELATED)
                        .add(layout.createParallelGroup(org.jdesktop.layout.GroupLayout.LEADING, false)
                            .add(txtUser)
                            .add(txtPassword, org.jdesktop.layout.GroupLayout.DEFAULT_SIZE, 115, Short.MAX_VALUE))
                        .addPreferredGap(org.jdesktop.layout.LayoutStyle.RELATED, 132, Short.MAX_VALUE))
                    .add(layout.createParallelGroup(org.jdesktop.layout.GroupLayout.TRAILING, false)
                        .add(org.jdesktop.layout.GroupLayout.LEADING, layout.createSequentialGroup()
                            .add(btnCancel)
                            .addPreferredGap(org.jdesktop.layout.LayoutStyle.RELATED, org.jdesktop.layout.GroupLayout.DEFAULT_SIZE, Short.MAX_VALUE)
                            .add(btnLogin))
                        .add(org.jdesktop.layout.GroupLayout.LEADING, pImageLoc, org.jdesktop.layout.GroupLayout.PREFERRED_SIZE, 391, org.jdesktop.layout.GroupLayout.PREFERRED_SIZE)))
                .addContainerGap())
        );
        layout.setVerticalGroup(
            layout.createParallelGroup(org.jdesktop.layout.GroupLayout.LEADING)
            .add(layout.createSequentialGroup()
                .addContainerGap()
                .add(pImageLoc, org.jdesktop.layout.GroupLayout.PREFERRED_SIZE, 73, org.jdesktop.layout.GroupLayout.PREFERRED_SIZE)
                .addPreferredGap(org.jdesktop.layout.LayoutStyle.RELATED)
                .add(layout.createParallelGroup(org.jdesktop.layout.GroupLayout.BASELINE)
                    .add(jLabel1)
                    .add(txtUser, org.jdesktop.layout.GroupLayout.PREFERRED_SIZE, org.jdesktop.layout.GroupLayout.DEFAULT_SIZE, org.jdesktop.layout.GroupLayout.PREFERRED_SIZE))
                .addPreferredGap(org.jdesktop.layout.LayoutStyle.RELATED)
                .add(layout.createParallelGroup(org.jdesktop.layout.GroupLayout.BASELINE)
                    .add(jLabel2)
                    .add(txtPassword, org.jdesktop.layout.GroupLayout.PREFERRED_SIZE, org.jdesktop.layout.GroupLayout.DEFAULT_SIZE, org.jdesktop.layout.GroupLayout.PREFERRED_SIZE))
                .addPreferredGap(org.jdesktop.layout.LayoutStyle.RELATED, 9, Short.MAX_VALUE)
                .add(layout.createParallelGroup(org.jdesktop.layout.GroupLayout.BASELINE)
                    .add(btnCancel)
                    .add(btnLogin))
                .addContainerGap())
        );
        pack();
    }// </editor-fold>//GEN-END:initComponents
    
    
    
    // Variables declaration - do not modify//GEN-BEGIN:variables
    private javax.swing.JButton btnCancel;
    private javax.swing.JButton btnLogin;
    private javax.swing.JLabel jLabel1;
    private javax.swing.JLabel jLabel2;
    private javax.swing.JPanel pImageLoc;
    private javax.swing.JPasswordField txtPassword;
    private javax.swing.JTextField txtUser;
    // End of variables declaration//GEN-END:variables
    
}
