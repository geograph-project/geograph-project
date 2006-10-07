/*
 * GeoLogin.java
 *
 * Created on 20 August 2006, 16:19
 */

package net.brassedoff;

import java.awt.Toolkit;
import java.awt.event.ActionEvent;
import java.awt.event.ActionListener;
import java.io.BufferedWriter;
import java.io.FileWriter;
import javax.swing.JOptionPane;
import org.apache.commons.httpclient.HttpClient;
import org.apache.commons.httpclient.methods.GetMethod;

/**
 *
 * @author  david
 */
public class GeoLogin extends javax.swing.JDialog implements ActionListener{
    
    /** Creates new form GeoLogin */
    public GeoLogin(java.awt.Frame parent, boolean modal) {
        super(parent, modal);
        initComponents();
        
        btnLogin.addActionListener(this);
        btnCancel.addActionListener(this);
    }
    
    public void actionPerformed(ActionEvent ae) {
        String action = ae.getActionCommand();
        
        if (action.equals("Cancel")) {
            
            // user wants out
            
            System.exit(1);
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
        jPanel1 = new javax.swing.JPanel();
        jLabel3 = new javax.swing.JLabel();
        btnLogin = new javax.swing.JButton();
        btnCancel = new javax.swing.JButton();

        setDefaultCloseOperation(javax.swing.WindowConstants.DISPOSE_ON_CLOSE);
        jLabel1.setText("Geograph user name:");

        jLabel2.setText("Geograph password:");

        jPanel1.setBackground(new java.awt.Color(0, 51, 255));
        jPanel1.setBorder(javax.swing.BorderFactory.createEtchedBorder(new java.awt.Color(0, 102, 204), new java.awt.Color(0, 0, 153)));
        jLabel3.setFont(new java.awt.Font("Dialog", 1, 24));
        jLabel3.setForeground(new java.awt.Color(255, 255, 255));
        jLabel3.setText("Photograph every grid square!");

        org.jdesktop.layout.GroupLayout jPanel1Layout = new org.jdesktop.layout.GroupLayout(jPanel1);
        jPanel1.setLayout(jPanel1Layout);
        jPanel1Layout.setHorizontalGroup(
            jPanel1Layout.createParallelGroup(org.jdesktop.layout.GroupLayout.LEADING)
            .add(jPanel1Layout.createSequentialGroup()
                .addContainerGap()
                .add(jLabel3)
                .addContainerGap(org.jdesktop.layout.GroupLayout.DEFAULT_SIZE, Short.MAX_VALUE))
        );
        jPanel1Layout.setVerticalGroup(
            jPanel1Layout.createParallelGroup(org.jdesktop.layout.GroupLayout.LEADING)
            .add(jPanel1Layout.createSequentialGroup()
                .addContainerGap()
                .add(jLabel3)
                .addContainerGap(31, Short.MAX_VALUE))
        );

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
                        .add(layout.createParallelGroup(org.jdesktop.layout.GroupLayout.LEADING)
                            .add(jLabel1)
                            .add(jLabel2))
                        .addPreferredGap(org.jdesktop.layout.LayoutStyle.RELATED)
                        .add(layout.createParallelGroup(org.jdesktop.layout.GroupLayout.LEADING, false)
                            .add(txtUser)
                            .add(txtPassword, org.jdesktop.layout.GroupLayout.DEFAULT_SIZE, 115, Short.MAX_VALUE)))
                    .add(layout.createParallelGroup(org.jdesktop.layout.GroupLayout.TRAILING, false)
                        .add(org.jdesktop.layout.GroupLayout.LEADING, layout.createSequentialGroup()
                            .add(btnCancel)
                            .addPreferredGap(org.jdesktop.layout.LayoutStyle.RELATED, org.jdesktop.layout.GroupLayout.DEFAULT_SIZE, Short.MAX_VALUE)
                            .add(btnLogin))
                        .add(org.jdesktop.layout.GroupLayout.LEADING, jPanel1, org.jdesktop.layout.GroupLayout.PREFERRED_SIZE, org.jdesktop.layout.GroupLayout.DEFAULT_SIZE, org.jdesktop.layout.GroupLayout.PREFERRED_SIZE)))
                .addContainerGap(org.jdesktop.layout.GroupLayout.DEFAULT_SIZE, Short.MAX_VALUE))
        );
        layout.setVerticalGroup(
            layout.createParallelGroup(org.jdesktop.layout.GroupLayout.LEADING)
            .add(layout.createSequentialGroup()
                .addContainerGap()
                .add(jPanel1, org.jdesktop.layout.GroupLayout.PREFERRED_SIZE, org.jdesktop.layout.GroupLayout.DEFAULT_SIZE, org.jdesktop.layout.GroupLayout.PREFERRED_SIZE)
                .addPreferredGap(org.jdesktop.layout.LayoutStyle.RELATED)
                .add(layout.createParallelGroup(org.jdesktop.layout.GroupLayout.BASELINE)
                    .add(jLabel1)
                    .add(txtUser, org.jdesktop.layout.GroupLayout.PREFERRED_SIZE, org.jdesktop.layout.GroupLayout.DEFAULT_SIZE, org.jdesktop.layout.GroupLayout.PREFERRED_SIZE))
                .addPreferredGap(org.jdesktop.layout.LayoutStyle.RELATED)
                .add(layout.createParallelGroup(org.jdesktop.layout.GroupLayout.BASELINE)
                    .add(jLabel2)
                    .add(txtPassword, org.jdesktop.layout.GroupLayout.PREFERRED_SIZE, org.jdesktop.layout.GroupLayout.DEFAULT_SIZE, org.jdesktop.layout.GroupLayout.PREFERRED_SIZE))
                .addPreferredGap(org.jdesktop.layout.LayoutStyle.RELATED, org.jdesktop.layout.GroupLayout.DEFAULT_SIZE, Short.MAX_VALUE)
                .add(layout.createParallelGroup(org.jdesktop.layout.GroupLayout.BASELINE)
                    .add(btnCancel)
                    .add(btnLogin))
                .addContainerGap())
        );
        pack();
    }// </editor-fold>//GEN-END:initComponents
    
    /**
     * @param args the command line arguments
     */
    public static void main(String args[]) {
        java.awt.EventQueue.invokeLater(new Runnable() {
            public void run() {
                new GeoLogin(new javax.swing.JFrame(), true).setVisible(true);
            }
        });
    }
    
    // Variables declaration - do not modify//GEN-BEGIN:variables
    private javax.swing.JButton btnCancel;
    private javax.swing.JButton btnLogin;
    private javax.swing.JLabel jLabel1;
    private javax.swing.JLabel jLabel2;
    private javax.swing.JLabel jLabel3;
    private javax.swing.JPanel jPanel1;
    private javax.swing.JPasswordField txtPassword;
    private javax.swing.JTextField txtUser;
    // End of variables declaration//GEN-END:variables
    
}
