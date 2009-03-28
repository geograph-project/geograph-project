/*
 * UploadManager.java
 *
 * Created on August 22, 2006, 12:37 PM
 */


package net.brassedoff;

import java.awt.Component;
import java.awt.Toolkit;
import java.awt.event.ActionEvent;
import java.awt.event.ActionListener;
import java.awt.event.MouseAdapter;
import java.awt.event.MouseEvent;
import java.awt.event.WindowAdapter;
import java.awt.event.WindowEvent;
import java.io.BufferedReader;
import java.io.BufferedWriter;
import java.io.File;
import java.io.FileNotFoundException;
import java.io.FileOutputStream;
import java.io.FileReader;
import java.io.FileWriter;
import java.io.IOException;
import java.util.Properties;
import java.util.Vector;
import javax.swing.JMenuItem;
import javax.swing.JOptionPane;
import javax.swing.JPopupMenu;
import javax.swing.table.AbstractTableModel;
import org.apache.commons.httpclient.HttpClient;
import org.apache.commons.httpclient.methods.PostMethod;
import org.apache.commons.httpclient.methods.multipart.FilePart;
import org.apache.commons.httpclient.methods.multipart.MultipartRequestEntity;
import org.apache.commons.httpclient.methods.multipart.Part;
import org.apache.commons.httpclient.methods.multipart.StringPart;

/**
 * This is the application main form. It handles the image queue and the
 * upload actions.
 *
 * @author  david
 */
public class UploadManager extends javax.swing.JFrame implements ActionListener {
    
    // define the popup menu
    
    JPopupMenu contextMenu = new JPopupMenu();
    JMenuItem addLine = new JMenuItem("Add line");
    JMenuItem editLine = new JMenuItem("Edit this line");
    JMenuItem deleteLine = new JMenuItem("Delete this line");
    
    Vector uploadData = new Vector();
    int [] fieldList = {1, 4, 10, 11};
    TableQueueModel tqm = new TableQueueModel();
    
    /** Creates new form UploadManager */
    public UploadManager() {
        initComponents();
        
        // setup the popup context menu (the long way round)
        
        contextMenu.add(addLine);
        addLine.setActionCommand("Add picture");
        addLine.addActionListener(this);
        
        contextMenu.add(editLine);
        editLine.setActionCommand("Change picture");
        editLine.addActionListener(this);
        
        contextMenu.add(new JPopupMenu.Separator());
        
        contextMenu.add(deleteLine);
        deleteLine.setActionCommand("Delete picture");
        deleteLine.addActionListener(this);
        
        // we need a mouse listener for the menu...
        
        jScrollPane1.addMouseListener(new MouseAdapter() {
            public void mousePressed(MouseEvent me) {
                QueueMouseClick(me);
            }
           public void mouseReleased(MouseEvent me) {
               QueueMouseClick(me);
           }            
        });
        
        tblQueue.addMouseListener(new MouseAdapter() {
           public void mousePressed(MouseEvent me) {
               QueueMouseClick(me);
           } 
           public void mouseReleased(MouseEvent me) {
               QueueMouseClick(me);
           }
        });
        
        menuAboutAbout.addActionListener(this);
        tblQueue.setModel(tqm);
        
        // set up listeners on the menu items
        
        menuFileLogin.addActionListener(this);
        menuFileSave.addActionListener(this);
        menuFileLoad.addActionListener(this);
        menuFileUpload.addActionListener(this);
        menuFileSettings.addActionListener(this);
        menuFileExit.addActionListener(this);
        menuItemAdd.addActionListener(this);
        menuItemEdit.addActionListener(this);
        menuItemDelete.addActionListener(this);
        menuItemPurge.addActionListener(this);
        
        // if no login (i.e. no cache), disable picture editing
        
        if (Main.noCache) {
            menuItem.setEnabled(false);
        }
        
        this.addWindowListener(new myWindowAdapter());
    }
    
    private void QueueMouseClick(MouseEvent me) {
        
        // Don't allow the popup if there's no cache present
        
        if (me.isPopupTrigger() && !Main.noCache) {
            // display the context menu
            
            contextMenu.show((Component) me.getSource(), me.getX(), me.getY());
        }
    }
    
    private void ExitApp() {
        
        // we're closing - dump the properties back to disk and save the image queue
        
        Properties propList = new Properties();
        propList.put("doresize", Main.doResize ? "true" : "false");
        propList.put("cachedirectory", Main.cacheDirectory);
        propList.put("gridrefFromImage", Main.gridrefFromImage ? "true" : "false");
        try {
            propList.store(new FileOutputStream("juppy.prop"), "Juppy properties");
        } catch (Exception ex) {
            Toolkit.getDefaultToolkit().beep();
            JOptionPane.showMessageDialog(null, "Error storing properties");
        }
        
        SaveQueue();
        
        System.exit(0);        
    }
    
    class myWindowAdapter extends WindowAdapter {
        
        public void windowClosing(WindowEvent e) {
        
            // no return from here...
            
            ExitApp();

        }
    }
    
    /** This method is called from within the constructor to
     * initialize the form.
     * WARNING: Do NOT modify this code. The content of this method is
     * always regenerated by the Form Editor.
     */
    // <editor-fold defaultstate="collapsed" desc=" Generated Code ">//GEN-BEGIN:initComponents
    private void initComponents() {
        jScrollPane1 = new javax.swing.JScrollPane();
        tblQueue = new javax.swing.JTable();
        jPanel1 = new javax.swing.JPanel();
        jScrollPane2 = new javax.swing.JScrollPane();
        txtProgress = new javax.swing.JTextArea();
        menuBar = new javax.swing.JMenuBar();
        menuFile = new javax.swing.JMenu();
        menuFileLogin = new javax.swing.JMenuItem();
        jSeparator1 = new javax.swing.JSeparator();
        menuFileSave = new javax.swing.JMenuItem();
        menuFileLoad = new javax.swing.JMenuItem();
        jSeparator2 = new javax.swing.JSeparator();
        menuFileUpload = new javax.swing.JMenuItem();
        menuFileSettings = new javax.swing.JMenuItem();
        jSeparator4 = new javax.swing.JSeparator();
        menuFileExit = new javax.swing.JMenuItem();
        menuItem = new javax.swing.JMenu();
        menuItemAdd = new javax.swing.JMenuItem();
        menuItemEdit = new javax.swing.JMenuItem();
        menuItemDelete = new javax.swing.JMenuItem();
        jSeparator3 = new javax.swing.JSeparator();
        menuItemPurge = new javax.swing.JMenuItem();
        menuAbout = new javax.swing.JMenu();
        menuAboutAbout = new javax.swing.JMenuItem();

        setDefaultCloseOperation(javax.swing.WindowConstants.DO_NOTHING_ON_CLOSE);
        setTitle("Geograph JUppy");
        tblQueue.setModel(new javax.swing.table.DefaultTableModel(
            new Object [][] {
                {null, null, null, null},
                {null, null, null, null},
                {null, null, null, null},
                {null, null, null, null}
            },
            new String [] {
                "Title 1", "Title 2", "Title 3", "Title 4"
            }
        ));
        jScrollPane1.setViewportView(tblQueue);

        jPanel1.setBorder(javax.swing.BorderFactory.createLineBorder(new java.awt.Color(0, 0, 0)));
        txtProgress.setColumns(20);
        txtProgress.setRows(5);
        jScrollPane2.setViewportView(txtProgress);

        org.jdesktop.layout.GroupLayout jPanel1Layout = new org.jdesktop.layout.GroupLayout(jPanel1);
        jPanel1.setLayout(jPanel1Layout);
        jPanel1Layout.setHorizontalGroup(
            jPanel1Layout.createParallelGroup(org.jdesktop.layout.GroupLayout.LEADING)
            .add(jScrollPane2, org.jdesktop.layout.GroupLayout.DEFAULT_SIZE, 549, Short.MAX_VALUE)
        );
        jPanel1Layout.setVerticalGroup(
            jPanel1Layout.createParallelGroup(org.jdesktop.layout.GroupLayout.LEADING)
            .add(jScrollPane2, org.jdesktop.layout.GroupLayout.DEFAULT_SIZE, 143, Short.MAX_VALUE)
        );

        menuFile.setText("File");
        menuFileLogin.setText("Geograph login");
        menuFile.add(menuFileLogin);

        menuFile.add(jSeparator1);

        menuFileSave.setIcon(new javax.swing.ImageIcon(getClass().getResource("/document-save.png")));
        menuFileSave.setText("Save queue");
        menuFileSave.setActionCommand("Save");
        menuFile.add(menuFileSave);

        menuFileLoad.setIcon(new javax.swing.ImageIcon(getClass().getResource("/document-open.png")));
        menuFileLoad.setText("Load queue");
        menuFileLoad.setActionCommand("Load");
        menuFile.add(menuFileLoad);

        menuFile.add(jSeparator2);

        menuFileUpload.setIcon(new javax.swing.ImageIcon(getClass().getResource("/view-refresh.png")));
        menuFileUpload.setText("Upload");
        menuFile.add(menuFileUpload);

        menuFileSettings.setText("Settings");
        menuFile.add(menuFileSettings);

        menuFile.add(jSeparator4);

        menuFileExit.setText("Exit");
        menuFile.add(menuFileExit);

        menuBar.add(menuFile);

        menuItem.setText("Items");
        menuItem.setToolTipText("This will be disabled until you've logged in to Geograph once");
        menuItemAdd.setIcon(new javax.swing.ImageIcon(getClass().getResource("/camera-photo.png")));
        menuItemAdd.setText("Add picture");
        menuItem.add(menuItemAdd);

        menuItemEdit.setIcon(new javax.swing.ImageIcon(getClass().getResource("/applications-multimedia.png")));
        menuItemEdit.setText("Change current picture");
        menuItemEdit.setActionCommand("Change picture");
        menuItem.add(menuItemEdit);

        menuItemDelete.setIcon(new javax.swing.ImageIcon(getClass().getResource("/user-trash-full.png")));
        menuItemDelete.setText("Delete picture");
        menuItem.add(menuItemDelete);

        menuItem.add(jSeparator3);

        menuItemPurge.setText("Purge uploaded images");
        menuItemPurge.setActionCommand("Purge");
        menuItem.add(menuItemPurge);

        menuBar.add(menuItem);

        menuAbout.setText("About");
        menuAboutAbout.setText("About");
        menuAbout.add(menuAboutAbout);

        menuBar.add(menuAbout);

        setJMenuBar(menuBar);

        org.jdesktop.layout.GroupLayout layout = new org.jdesktop.layout.GroupLayout(getContentPane());
        getContentPane().setLayout(layout);
        layout.setHorizontalGroup(
            layout.createParallelGroup(org.jdesktop.layout.GroupLayout.LEADING)
            .add(org.jdesktop.layout.GroupLayout.TRAILING, layout.createSequentialGroup()
                .addContainerGap()
                .add(layout.createParallelGroup(org.jdesktop.layout.GroupLayout.TRAILING)
                    .add(org.jdesktop.layout.GroupLayout.LEADING, jPanel1, org.jdesktop.layout.GroupLayout.DEFAULT_SIZE, org.jdesktop.layout.GroupLayout.DEFAULT_SIZE, Short.MAX_VALUE)
                    .add(org.jdesktop.layout.GroupLayout.LEADING, jScrollPane1, org.jdesktop.layout.GroupLayout.DEFAULT_SIZE, 551, Short.MAX_VALUE))
                .addContainerGap())
        );
        layout.setVerticalGroup(
            layout.createParallelGroup(org.jdesktop.layout.GroupLayout.LEADING)
            .add(layout.createSequentialGroup()
                .addContainerGap()
                .add(jScrollPane1, org.jdesktop.layout.GroupLayout.PREFERRED_SIZE, 191, org.jdesktop.layout.GroupLayout.PREFERRED_SIZE)
                .addPreferredGap(org.jdesktop.layout.LayoutStyle.RELATED)
                .add(jPanel1, org.jdesktop.layout.GroupLayout.PREFERRED_SIZE, org.jdesktop.layout.GroupLayout.DEFAULT_SIZE, org.jdesktop.layout.GroupLayout.PREFERRED_SIZE)
                .addContainerGap(org.jdesktop.layout.GroupLayout.DEFAULT_SIZE, Short.MAX_VALUE))
        );
        pack();
    }// </editor-fold>//GEN-END:initComponents
    
    /**
     *
     * Static main method. Handle creating the rest of the application.
     *
     * @param args the command line arguments
     */
    public static void main(String args[]) {
        java.awt.EventQueue.invokeLater(new Runnable() {
            public void run() {
                new UploadManager().setVisible(true);
            }
        });
    }
    
    /**
     *
     * All the main form actions are handled through this routine.
     *
     * @param ae Action event
     */
    public void actionPerformed(ActionEvent ae) {
        
        // general handler for all action events
        
        String action = ae.getActionCommand();
        
        if (action.equals("Geograph login")) {
            GeographLogin();
            
        }
        else if (action.equals("Save")) {
            
            SaveQueue();
            
        } else if (action.equals("Load")) {
            
            LoadQueue();
            
        } else if (action.equals("Upload")) {
            
            // this is where the fun starts...
            
            UploadQueue();
            
        } else if (action.equals("Exit")) {
            
            ExitApp();

        } else if(action.equals("Add picture")) {
            
            // add new picture to queue
            
            AddPicture();
            
        } else if(action.equals("Delete picture")) {
            
            // delete the current row
            
            DeletePicture();

        } else if(action.equals("Change picture")) {

            
            // Change current picture
            
            ChangePicture();
            
        } else if(action.equals("Purge")) {
            
            // purge queue of uploaded items
            
            PurgeQueue();
            
        } else if(action.equals("Settings")) {
            
            // Settings (PropertyForm) dialog box
            
            EditSettings();
            
        
        } else if (action.equals("About")) {
            
            // display about dialog box
            
            AboutBox ab = new AboutBox(this, true);
            ab.setVisible(true);

        }
        
    }

    /** 
     *
     * Check that the upload queue looks good before attempting the upload.
     * Currently, this involves checking that we have all the image files
     * intact which may or may not be an issue if local resizing has been
     * enabled part way through a queue buildup
     *
     **/
    
    private int OKToUpload() {
        
        int rc = -1;
        
        for (int i = 0; i < uploadData.size(); i++) {
            File f = new File(((Object [])uploadData.elementAt(i))[0].toString());
            if (!f.exists()) {
                rc = i + 1;
                break;
            }
        }
        return rc;
    }
    
    private void UploadQueue() {
        
        String [] dirCode = {"N", "NNE", "NE", "ENE", "E", "ESE", "SE", "SSE", "S", "SSW", "SW", "WSW", "W", "WNW", "NW", "NNW"};
        String [] angle = {"0", "22", "45", "67", "90", "112", "135", "157", "180", "202", "225", "247", "270", "292", "315", "337"};
        
        // this is the real fun
        // we're going to take each entry, one at a time, and upload
        // it to the server, awaiting a response afer each process
        
        int checkUpload = OKToUpload();
        if (checkUpload > 0) {
            Toolkit.getDefaultToolkit().beep();
            JOptionPane.showMessageDialog(this, "Missing file on line " + checkUpload + " in image queue");
            return;
        }
        
        // clear the progress field
        
        txtProgress.setText("");
        
        // If we get an error, we report it and stop, preserving what's left


        int warningCount = 0;
        
        for (int i = 0; i < uploadData.size(); i++) {
            Object [] line = (String []) uploadData.elementAt(i);
            
            // if the status is 'OK', it's already been uploaded
            if (!line[10].toString().toUpperCase().equals("OK")) {

                HttpClient htc = new HttpClient();
                PostMethod post = new PostMethod(Main.geoURL + "?action=upload");                
        
                // ok, I know this is a ludge... some day I'll engineer it out...
                
                String direction = line[3].toString().toUpperCase();
                String newAngle = "0";
                for (int j = 0; j < dirCode.length; j++) {
                    if (direction.equals(dirCode[j])) {
                        newAngle = angle[j];
                    }
                }
                
                try {
                    File f = new File(line[0].toString());
                    Part [] parts = {
                        new StringPart("action", "upload"),
                        new StringPart("userid", Integer.toString(Main.geoUserid)),
                        new StringPart("subject", line[1].toString()),
                        new StringPart("photographer", line[2].toString()),
                        new StringPart("direction", newAngle),
                        new StringPart("title", line[4].toString()),
                        new StringPart("comments", line[5].toString()),
                        new StringPart("feature", line[6].toString()),
                        new StringPart("date", line[7].toString()),
                        new StringPart("supplemental", line[8].toString()),
                        new StringPart("validation", Main.validationToken),
                        new StringPart("cclicence", "I grant you the permission to use this"
                                 + " submission under the terms of the Creative Commons by-sa-2.0 licence"),
                        new FilePart("uploadfile", f)
                    };
                    
                    post.setRequestEntity(new MultipartRequestEntity(parts, post.getParams()));
                    htc.executeMethod(post);
                    String response = post.getResponseBodyAsString();
                    System.out.println("response from server was " + response + "\n");

                    if (!XMLHandler.getXMLField(response, "status").equals("OK")) {
                        warningCount++;
                    }

                    // what we need to do here is to put the response in the grid

                    String uploadStatus = XMLHandler.getXMLField(response, "status");
                    tqm.setValueAt(uploadStatus, i, 2);

                    System.out.println("Uploaded " + line[4].toString() + " :: " + uploadStatus);
                    txtProgress.setText(txtProgress.getText() + "\n" + "Uploaded " + line[4].toString() + 
                            " :: " + uploadStatus);
                    txtProgress.repaint();
                    post.releaseConnection();

                    if (uploadStatus.toUpperCase().equals("NOT LOGGED IN")) {
                        txtProgress.setText(txtProgress.getText() + "\n" + "Upload aborted");
                        break;
                    }

                    

                } catch (FileNotFoundException ex) {
                    Toolkit.getDefaultToolkit().beep();
                    JOptionPane.showMessageDialog(this, ex.getMessage());
                } catch (IOException ex) {
                    Toolkit.getDefaultToolkit().beep();
                    JOptionPane.showMessageDialog(this, ex.getMessage());
                } finally {
                    post.releaseConnection();
                }
            }
            
        }

        // were there any warnings on upload?
        
        if (warningCount != 0) {
            Toolkit.getDefaultToolkit().beep();
            JOptionPane.showMessageDialog(this, "There were " + warningCount + " errors on the upload");
        } else {
            JOptionPane.showMessageDialog(this, "Upload completed OK");
        }
        
    }
    
    private void GeographLogin() {
        
        GeoLogin gl = new GeoLogin(this, true);
        gl.setVisible(true);
        gl.dispose();
        if (!Main.noCache) {
            // must have logged in ok so enable the items menu
            
            menuItem.setEnabled(true);
        }
        
    }
    
    private void PurgeQueue() {
        
        tqm.PurgeUploadedItems();
    }
    
    private void DeletePicture() {

        // delete the current row from the table - there should be only one row selected
            
        if (tblQueue.getSelectedRowCount() != 1) {
            JOptionPane.showMessageDialog(this, "Please select one row to be deleted");
            Toolkit.getDefaultToolkit().beep();
            return;
        }

        int currentRow = tblQueue.getSelectedRow();
        tqm.deleteRow(currentRow);
        
    }
    
    private void EditSettings() {
        PropertyForm pf = new PropertyForm(this, true);
        pf.setVisible(true);
        pf.dispose();
    }
    
    private void AddPicture() {
        
        // to add a new picture, call up an instance of UploadForm, capture
        // the data and stuff it into the upload queue vector, then add the 
        // item to the table
        
        UploadForm uf = new UploadForm(this, true);
        uf.setVisible(true);
        
        if (uf.acceptFlag) {
            String [] newRow = new String[20];
            for (int i = 0; i < uf.editData.length; i++) {
                newRow[i] = new String(uf.editData[i]);
            }
            tqm.addRow(newRow);
        }
        uf.dispose();
        
    }
    
    private void ChangePicture() {
        
        // get the data out of the vector, populate the array in the
        // UploadForm class and kick it off
        
        if (tblQueue.getSelectedRowCount() != 1) {
            Toolkit.getDefaultToolkit().beep();
            JOptionPane.showMessageDialog(this, "Select a single row to change");
            return;
        }
        
        // create the edit form
        
        UploadForm uf = new UploadForm(this, true);
        
        // copy the data in
        
        String [] qData = (String []) uploadData.elementAt(tblQueue.getSelectedRow());
        for (int i = 0; i < qData.length; i++) {
            uf.editData[i] = new String(qData[i].toString());
        }
        
        // force the fields to populate
        
        uf.populateFields();
        uf.setVisible(true);

        // if the accept flag is set, create a new row and replace the old one
        
        if (uf.acceptFlag) {
            String [] newRow = new String[20];
            for (int i = 0; i < uf.editData.length; i++) {
                newRow[i] = new String(uf.editData[i]);
            }
            uploadData.setElementAt(newRow, tblQueue.getSelectedRow());
            tqm.fireTableDataChanged();
        }
        
    }

    
    private void SaveQueue() {
        BufferedWriter op;
        try {
            op = new BufferedWriter(new FileWriter("juppyqueue.xml"));
        } catch (Exception ex) {
            Toolkit.getDefaultToolkit().beep();
            JOptionPane.showMessageDialog(this, "Unable to open 'juppyqueue.xml'");
            return;
        }
        try {
            op.write("<juppy>\n");
            op.write("<count>" + uploadData.size() + "</count>\n");
            for (int i = 0; i < uploadData.size(); i++) {
                String [] thisLine = (String []) uploadData.elementAt(i);
                
                op.write("<line" + i + ">");
                op.write("<juppyline>");
                op.write("<filename>" + thisLine[0] + "</filename>\n");
                op.write("<subject>" + thisLine[1] + "</subject>\n");
                op.write("<photographer>" + thisLine[2] + "</photographer>\n");
                op.write("<direction>" + thisLine[3] + "</direction>\n");
                op.write("<title>" + thisLine[4] + "</title>\n");
                op.write("<comments>" + thisLine[5] + "</comments>\n");
                op.write("<feature>" + thisLine[6] + "</feature>\n");
                op.write("<date>" + thisLine[7] + "</date>\n");
                op.write("<supplemental>" + thisLine[8] + "</supplemental>\n");
                op.write("<cclicence>" + thisLine[9] + "</cclicence>\n");
                op.write("<uploadflag>" + thisLine[10] + "</uploadflag>\n");
                op.write("<imagelocation>" + thisLine[11] + "</imagelocation>");
                op.write("</juppyline>");
                op.write("</line" + i + ">\n");
            }
            op.write("</juppy>");
            op.close();

        } catch (Exception ex) {
            
        }
        
    }
    
    private void LoadQueue() {
        BufferedReader inp;
        
        try {
            inp = new BufferedReader(new FileReader("juppyqueue.xml"));
        } catch (Exception ex) {
            JOptionPane.showMessageDialog(this, "Unable to open juppyqueue.xml");
            Toolkit.getDefaultToolkit().beep();
            return;
        }
        
        StringBuffer xml = new StringBuffer(5000);
        String line;
        boolean eof = false;
        while (!eof) {
            try {
                line = inp.readLine();
            } catch (Exception ex) {
                JOptionPane.showMessageDialog(this, "IO error on juppyqueue.xml");
                Toolkit.getDefaultToolkit().beep();
                return;
            }
            if (line == null) {
                eof = true;
            } else {
                xml.append(line);
            }
        }
    
        String xmlString = new String(xml);
        int lineCount;
        try {
            lineCount = Integer.parseInt(XMLHandler.getXMLField(xmlString, "count"));
        } catch (Exception ex) {
            JOptionPane.showMessageDialog(this, "Error - couldn't get line count from juppyqueue.xml");
            Toolkit.getDefaultToolkit().beep();
            return;
        }
        
        // now we know how many lines, we can reconstruct the data
        for (int i = 0; i < lineCount; i++) {
            String xmlLine = new String(XMLHandler.getXMLField(xmlString, "line" + i));
            String [] newLine = new String [20];
            newLine[0] = new String(XMLHandler.getXMLField(xmlLine, "filename"));
            newLine[1] = new String(XMLHandler.getXMLField(xmlLine, "subject"));
            newLine[2] = new String(XMLHandler.getXMLField(xmlLine, "photographer"));
            newLine[3] = new String(XMLHandler.getXMLField(xmlLine, "direction"));
            newLine[4] = new String(XMLHandler.getXMLField(xmlLine, "title"));
            newLine[5] = new String(XMLHandler.getXMLField(xmlLine, "comments"));
            newLine[6] = new String(XMLHandler.getXMLField(xmlLine, "feature"));
            newLine[7] = new String(XMLHandler.getXMLField(xmlLine, "date"));
            newLine[8] = new String(XMLHandler.getXMLField(xmlLine, "supplemental"));
            newLine[9] = new String(XMLHandler.getXMLField(xmlLine, "cclicence"));
            newLine[10] = new String(XMLHandler.getXMLField(xmlLine, "uploadflag"));
            newLine[11] = new String(XMLHandler.getXMLField(xmlLine, "imagelocation"));
            for (int j = 12; j < 20; j++) {
                newLine[j] = new String("");
            }
            
            tqm.addRow(newLine);
        }
    }
    
    class TableQueueModel extends AbstractTableModel {

        // Note the break with tradition - the vector holding the table
        // data is provided by the outer class - we're using the same data
        // to avoid having a cut-down version for the table model.
        
        String [] columnNames = {"gridref", "title", "uploadstatus", "imageloc"};
        
        TableQueueModel() {
            super();
        }
        
        public Object getValueAt(int row, int col) {
            int displayCol = fieldList[col];
            return (Object) ((Object []) uploadData.elementAt(row))[displayCol];
        }
        
        public void setValueAt(Object o, int row, int col) {
        
            int displayCol = fieldList[col];
            Object [] line = (Object []) uploadData.elementAt(row);
            line[displayCol] = o;
            fireTableCellUpdated(row, col);
            
        }

    
        private void PurgeUploadedItems() {

            // if status is "ok", delete the row
            
            int i = 0;
            while (i < uploadData.size()) {
                // if the status is 'ok', delete the line
                String [] thisLine = (String []) uploadData.elementAt(i);
                if (thisLine[10].toUpperCase().equals("OK")) {
                    uploadData.removeElementAt(i);
                } else {
                    i++;
                }
            }
            fireTableDataChanged();
        }        
        
        public void addRow(Object o) {
            uploadData.addElement(o);
            fireTableDataChanged();
        }
        
        public void deleteRow(int row) {
            uploadData.removeElementAt(row);
            fireTableDataChanged();
        }
        
        public int getRowCount() {return uploadData.size();}
        
        public int getColumnCount() {return columnNames.length;}
        
        public String getColumnName(int col) {return columnNames[col];}
        public boolean isCellEditable(int row, int col) {return false;}
        
    }
    
    // Variables declaration - do not modify//GEN-BEGIN:variables
    private javax.swing.JPanel jPanel1;
    private javax.swing.JScrollPane jScrollPane1;
    private javax.swing.JScrollPane jScrollPane2;
    private javax.swing.JSeparator jSeparator1;
    private javax.swing.JSeparator jSeparator2;
    private javax.swing.JSeparator jSeparator3;
    private javax.swing.JSeparator jSeparator4;
    private javax.swing.JMenu menuAbout;
    private javax.swing.JMenuItem menuAboutAbout;
    private javax.swing.JMenuBar menuBar;
    private javax.swing.JMenu menuFile;
    private javax.swing.JMenuItem menuFileExit;
    private javax.swing.JMenuItem menuFileLoad;
    private javax.swing.JMenuItem menuFileLogin;
    private javax.swing.JMenuItem menuFileSave;
    private javax.swing.JMenuItem menuFileSettings;
    private javax.swing.JMenuItem menuFileUpload;
    private javax.swing.JMenu menuItem;
    private javax.swing.JMenuItem menuItemAdd;
    private javax.swing.JMenuItem menuItemDelete;
    private javax.swing.JMenuItem menuItemEdit;
    private javax.swing.JMenuItem menuItemPurge;
    private javax.swing.JTable tblQueue;
    private javax.swing.JTextArea txtProgress;
    // End of variables declaration//GEN-END:variables
    
}
