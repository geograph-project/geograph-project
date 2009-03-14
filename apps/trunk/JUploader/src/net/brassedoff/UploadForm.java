/*
 * UploadForm.java
 *
 * Created on 18 August 2006, 20:38
 */

package net.brassedoff;

import java.awt.Color;
import java.awt.Graphics;
import java.awt.Graphics2D;
import java.awt.Image;
import java.awt.Toolkit;
import java.awt.event.ActionEvent;
import java.awt.event.ActionListener;
import java.awt.event.FocusAdapter;
import java.awt.event.FocusEvent;
import java.awt.event.KeyAdapter;
import java.awt.event.KeyEvent;
import java.awt.geom.AffineTransform;
import java.awt.image.BufferedImage;
import java.awt.image.ImageObserver;
import java.io.File;
import java.io.IOException;
import java.text.DateFormat;
import java.text.ParseException;
import java.util.Date;
import java.util.Locale;
import java.util.regex.Matcher;
import java.util.regex.Pattern;
import javax.imageio.ImageIO;
import javax.swing.JFileChooser;
import javax.swing.JOptionPane;
import javax.swing.filechooser.FileFilter;

/**
 *
 * Main form that handles the uploading of a single image to the
 * Geograph server. The class does populates some of the dialogs with
 * data from the server (geographical category for instance)
 *
 * @author  david
 */
public class UploadForm extends javax.swing.JDialog implements ActionListener {
    
    public String [] editData = new String [20];
    public boolean acceptFlag;
    ImagePreview preview = new ImagePreview();
    Compass compass = new Compass();
    
    /** Creates new form UploadForm */
    public UploadForm(java.awt.Frame parent, boolean modal) {
        super(parent, modal);
        initComponents();
        
        
        /*
        pnlCompass.add(compass, BorderLayout.CENTER);
        pnlCompass.repaint();
         */
        
        // my initialisation stuff (listeners etc)
        btnImagefile.addActionListener(this);
        btnToday.addActionListener(this);
        btnReset.addActionListener(this);
        btnUpload.addActionListener(this);
        
        txtPhotographer.addKeyListener(new PhotographerKeyAdapter());
        System.out.println("hello");

        // OK, I know I don't normally use anonymous inner classes...
        txtPhotographer.addFocusListener(new FocusAdapter() {
            public void focusLost(FocusEvent fe) {
                CheckBearing();
            }
        });
        
        txtSubject.addFocusListener(new FocusAdapter() {
            public void focusLost(FocusEvent fe) {
                CheckBearing();
            }
        });
        
        // fill the feature combobox
        cmbGeoFeature.removeAllItems();
        
        // it was thought that a null first entry might be useful
        
        cmbGeoFeature.addItem("");
        
        for (int i = 0; i < Main.imageClassList.length; i++) {
            cmbGeoFeature.addItem(Main.imageClassList[i]);
        }
        
        acceptFlag = false;
        
        // sort out the image preview
        
        pnlPreview.add(preview, java.awt.BorderLayout.CENTER);
        
        // status label...
        lblStatus.setText("");
        
        
    }
    
    /**
     * Check the photographer and subject position. Automatically calculate the view direction.
     */
    public void CheckBearing() {
        String subject = txtSubject.getText().trim();
        String photographer = txtPhotographer.getText().trim();
        
        try {
            int bearing = OSGridRef.calculateBearing(photographer, subject);
            int newIndex = OSGridRef.bearingToIndex(bearing);
            cmbDirection.setSelectedIndex(newIndex + 1);
        } catch (Exception ex) {
            // probably don't want to do anything here, just ignore the error
            if (Debug.ON) {
                System.out.println("Invalid direction parameters passed\n" + ex.toString());
            }
        }
    }
    
    public class PhotographerKeyAdapter extends KeyAdapter {
        /**
         * This provides a simple paste facility for the photographer position from 
         * the subject position.
         * @param ke 
         */
        public void keyPressed(KeyEvent ke) {

            // we're looking for a Ctrl-C in the photographer field to copy in the
            // subject
            
            if ((ke.getKeyCode() == ke.VK_C) && ke.getKeyModifiersText(ke.getModifiers()).equals("Ctrl")) {
                txtPhotographer.setText(txtSubject.getText());
            
            }
        }
    }
    
    public void populateFields() {
        
        // populate the fields from the editData array (usually called prior
        // to an edit)
        
        txtImagefile.setText(editData[0]);
        
        preview.setFilename(editData[0]);
        
        txtSubject.setText(editData[1]);
        txtPhotographer.setText(editData[2]);
        cmbDirection.setSelectedItem(editData[3]);
        txtImageTitle.setText(editData[4]);
        txtImageComments.setText(editData[5]);
        cmbGeoFeature.setSelectedItem(editData[6]);
        txtPhotoDate.setText(editData[7]);
        chkSupplemental.setSelected(editData[8].equals("Y") ? true : false);
        chkCCLicence.setSelected(editData[9].equals("Y") ? true : false);
        
        lblStatus.setText("");
        
    }
    
    /** This method is called from within the constructor to
     * initialize the form.
     * WARNING: Do NOT modify this code. The content of this method is
     * always regenerated by the Form Editor.
     */
    // <editor-fold defaultstate="collapsed" desc=" Generated Code ">//GEN-BEGIN:initComponents
    private void initComponents() {
        jLabel2 = new javax.swing.JLabel();
        jLabel3 = new javax.swing.JLabel();
        jLabel4 = new javax.swing.JLabel();
        cmbDirection = new javax.swing.JComboBox();
        jLabel5 = new javax.swing.JLabel();
        jLabel6 = new javax.swing.JLabel();
        jLabel7 = new javax.swing.JLabel();
        jLabel8 = new javax.swing.JLabel();
        btnUpload = new javax.swing.JButton();
        btnReset = new javax.swing.JButton();
        txtImagefile = new javax.swing.JTextField();
        txtSubject = new javax.swing.JTextField();
        txtPhotographer = new javax.swing.JTextField();
        txtImageTitle = new javax.swing.JTextField();
        jScrollPane1 = new javax.swing.JScrollPane();
        txtImageComments = new javax.swing.JTextArea();
        cmbGeoFeature = new javax.swing.JComboBox();
        txtPhotoDate = new javax.swing.JTextField();
        btnToday = new javax.swing.JButton();
        btnImagefile = new javax.swing.JButton();
        chkSupplemental = new javax.swing.JCheckBox();
        chkCCLicence = new javax.swing.JCheckBox();
        pnlPreview = new javax.swing.JPanel();
        lblStatus = new javax.swing.JLabel();
        pnlCompass = new javax.swing.JPanel();

        setTitle("Geograph uploader");
        setBackground(java.awt.Color.lightGray);
        setResizable(false);
        jLabel2.setText("Subject grid reference:");

        jLabel3.setText("Photographer grid reference:");

        jLabel4.setText("View direction:");

        cmbDirection.setModel(new javax.swing.DefaultComboBoxModel(new String[] { "", "North", "NNE", "NE", "ENE", "East", "ESE", "SE", "SSE", "South", "SSW", "SW", "WSW", "West", "WNW", "NW", "NNW" }));

        jLabel5.setText("Image title:");

        jLabel6.setText("Comment:");

        jLabel7.setText("Primary geographical feature:");

        jLabel8.setText("Date photo taken:");

        btnUpload.setText("Add to queue");

        btnReset.setText("Reset");

        txtImagefile.setEnabled(false);

        txtPhotographer.setToolTipText("Press CTRL-C to copy in subject grid reference");

        txtImageComments.setColumns(20);
        txtImageComments.setLineWrap(true);
        txtImageComments.setRows(5);
        txtImageComments.setWrapStyleWord(true);
        jScrollPane1.setViewportView(txtImageComments);

        cmbGeoFeature.setModel(new javax.swing.DefaultComboBoxModel(new String[] { "Item 1", "Item 2", "Item 3", "Item 4" }));

        btnToday.setText("Today");
        btnToday.setToolTipText("Use today's date for this image");

        btnImagefile.setText("Image file");

        chkSupplemental.setText("This is a supplemental image");
        chkSupplemental.setBorder(javax.swing.BorderFactory.createEmptyBorder(0, 0, 0, 0));
        chkSupplemental.setMargin(new java.awt.Insets(0, 0, 0, 0));

        chkCCLicence.setText("I agree to the use of the CC licence for this image");
        chkCCLicence.setBorder(javax.swing.BorderFactory.createEmptyBorder(0, 0, 0, 0));
        chkCCLicence.setMargin(new java.awt.Insets(0, 0, 0, 0));

        pnlPreview.setLayout(new java.awt.BorderLayout());

        pnlPreview.setBorder(javax.swing.BorderFactory.createEtchedBorder());
        pnlPreview.setPreferredSize(new java.awt.Dimension(100, 75));

        lblStatus.setText("jLabel1");

        pnlCompass.setLayout(new java.awt.BorderLayout());

        org.jdesktop.layout.GroupLayout layout = new org.jdesktop.layout.GroupLayout(getContentPane());
        getContentPane().setLayout(layout);
        layout.setHorizontalGroup(
            layout.createParallelGroup(org.jdesktop.layout.GroupLayout.LEADING)
            .add(org.jdesktop.layout.GroupLayout.TRAILING, layout.createSequentialGroup()
                .addContainerGap()
                .add(layout.createParallelGroup(org.jdesktop.layout.GroupLayout.LEADING)
                    .add(chkCCLicence, org.jdesktop.layout.GroupLayout.DEFAULT_SIZE, 568, Short.MAX_VALUE)
                    .add(chkSupplemental, org.jdesktop.layout.GroupLayout.DEFAULT_SIZE, 568, Short.MAX_VALUE)
                    .add(layout.createSequentialGroup()
                        .add(btnReset)
                        .addPreferredGap(org.jdesktop.layout.LayoutStyle.RELATED, 366, Short.MAX_VALUE)
                        .add(btnUpload, org.jdesktop.layout.GroupLayout.PREFERRED_SIZE, 134, org.jdesktop.layout.GroupLayout.PREFERRED_SIZE))
                    .add(layout.createSequentialGroup()
                        .add(layout.createParallelGroup(org.jdesktop.layout.GroupLayout.LEADING)
                            .add(jLabel7)
                            .add(jLabel8))
                        .addPreferredGap(org.jdesktop.layout.LayoutStyle.RELATED)
                        .add(layout.createParallelGroup(org.jdesktop.layout.GroupLayout.LEADING)
                            .add(layout.createSequentialGroup()
                                .add(txtPhotoDate, org.jdesktop.layout.GroupLayout.PREFERRED_SIZE, 104, org.jdesktop.layout.GroupLayout.PREFERRED_SIZE)
                                .addPreferredGap(org.jdesktop.layout.LayoutStyle.RELATED, 198, Short.MAX_VALUE)
                                .add(btnToday))
                            .add(cmbGeoFeature, 0, 374, Short.MAX_VALUE)))
                    .add(layout.createSequentialGroup()
                        .add(layout.createParallelGroup(org.jdesktop.layout.GroupLayout.LEADING)
                            .add(layout.createSequentialGroup()
                                .add(layout.createParallelGroup(org.jdesktop.layout.GroupLayout.LEADING)
                                    .add(jLabel3)
                                    .add(jLabel2)
                                    .add(jLabel4)
                                    .add(btnImagefile, org.jdesktop.layout.GroupLayout.PREFERRED_SIZE, 226, org.jdesktop.layout.GroupLayout.PREFERRED_SIZE))
                                .addPreferredGap(org.jdesktop.layout.LayoutStyle.RELATED)
                                .add(layout.createParallelGroup(org.jdesktop.layout.GroupLayout.LEADING)
                                    .add(txtImagefile, org.jdesktop.layout.GroupLayout.DEFAULT_SIZE, 222, Short.MAX_VALUE)
                                    .add(cmbDirection, 0, 222, Short.MAX_VALUE)
                                    .add(layout.createParallelGroup(org.jdesktop.layout.GroupLayout.TRAILING, false)
                                        .add(org.jdesktop.layout.GroupLayout.LEADING, txtPhotographer)
                                        .add(org.jdesktop.layout.GroupLayout.LEADING, txtSubject, org.jdesktop.layout.GroupLayout.DEFAULT_SIZE, 77, Short.MAX_VALUE))))
                            .add(layout.createSequentialGroup()
                                .add(layout.createParallelGroup(org.jdesktop.layout.GroupLayout.LEADING)
                                    .add(jLabel5)
                                    .add(jLabel6))
                                .addPreferredGap(org.jdesktop.layout.LayoutStyle.RELATED)
                                .add(layout.createParallelGroup(org.jdesktop.layout.GroupLayout.LEADING)
                                    .add(jScrollPane1, org.jdesktop.layout.GroupLayout.DEFAULT_SIZE, 372, Short.MAX_VALUE)
                                    .add(txtImageTitle, org.jdesktop.layout.GroupLayout.DEFAULT_SIZE, 372, Short.MAX_VALUE))
                                .addPreferredGap(org.jdesktop.layout.LayoutStyle.RELATED)))
                        .add(layout.createParallelGroup(org.jdesktop.layout.GroupLayout.LEADING)
                            .add(layout.createSequentialGroup()
                                .add(14, 14, 14)
                                .add(pnlPreview, org.jdesktop.layout.GroupLayout.PREFERRED_SIZE, org.jdesktop.layout.GroupLayout.DEFAULT_SIZE, org.jdesktop.layout.GroupLayout.PREFERRED_SIZE))
                            .add(layout.createSequentialGroup()
                                .addPreferredGap(org.jdesktop.layout.LayoutStyle.RELATED)
                                .add(pnlCompass, org.jdesktop.layout.GroupLayout.PREFERRED_SIZE, 50, org.jdesktop.layout.GroupLayout.PREFERRED_SIZE)))))
                .addContainerGap())
            .add(lblStatus, org.jdesktop.layout.GroupLayout.DEFAULT_SIZE, 592, Short.MAX_VALUE)
        );
        layout.setVerticalGroup(
            layout.createParallelGroup(org.jdesktop.layout.GroupLayout.LEADING)
            .add(layout.createSequentialGroup()
                .addContainerGap()
                .add(layout.createParallelGroup(org.jdesktop.layout.GroupLayout.LEADING)
                    .add(layout.createSequentialGroup()
                        .add(layout.createParallelGroup(org.jdesktop.layout.GroupLayout.BASELINE)
                            .add(btnImagefile)
                            .add(txtImagefile, org.jdesktop.layout.GroupLayout.PREFERRED_SIZE, org.jdesktop.layout.GroupLayout.DEFAULT_SIZE, org.jdesktop.layout.GroupLayout.PREFERRED_SIZE))
                        .addPreferredGap(org.jdesktop.layout.LayoutStyle.RELATED)
                        .add(layout.createParallelGroup(org.jdesktop.layout.GroupLayout.LEADING)
                            .add(layout.createSequentialGroup()
                                .add(jLabel2)
                                .addPreferredGap(org.jdesktop.layout.LayoutStyle.RELATED)
                                .add(jLabel3)
                                .add(17, 17, 17)
                                .add(jLabel4))
                            .add(layout.createSequentialGroup()
                                .add(txtSubject, org.jdesktop.layout.GroupLayout.PREFERRED_SIZE, org.jdesktop.layout.GroupLayout.DEFAULT_SIZE, org.jdesktop.layout.GroupLayout.PREFERRED_SIZE)
                                .addPreferredGap(org.jdesktop.layout.LayoutStyle.RELATED)
                                .add(txtPhotographer, org.jdesktop.layout.GroupLayout.PREFERRED_SIZE, org.jdesktop.layout.GroupLayout.DEFAULT_SIZE, org.jdesktop.layout.GroupLayout.PREFERRED_SIZE)
                                .addPreferredGap(org.jdesktop.layout.LayoutStyle.RELATED)
                                .add(layout.createParallelGroup(org.jdesktop.layout.GroupLayout.LEADING)
                                    .add(pnlCompass, org.jdesktop.layout.GroupLayout.PREFERRED_SIZE, 50, org.jdesktop.layout.GroupLayout.PREFERRED_SIZE)
                                    .add(layout.createSequentialGroup()
                                        .add(cmbDirection, org.jdesktop.layout.GroupLayout.PREFERRED_SIZE, org.jdesktop.layout.GroupLayout.DEFAULT_SIZE, org.jdesktop.layout.GroupLayout.PREFERRED_SIZE)
                                        .addPreferredGap(org.jdesktop.layout.LayoutStyle.RELATED)
                                        .add(layout.createParallelGroup(org.jdesktop.layout.GroupLayout.BASELINE)
                                            .add(jLabel5)
                                            .add(txtImageTitle, org.jdesktop.layout.GroupLayout.PREFERRED_SIZE, org.jdesktop.layout.GroupLayout.DEFAULT_SIZE, org.jdesktop.layout.GroupLayout.PREFERRED_SIZE))))))
                        .addPreferredGap(org.jdesktop.layout.LayoutStyle.RELATED)
                        .add(layout.createParallelGroup(org.jdesktop.layout.GroupLayout.LEADING)
                            .add(layout.createSequentialGroup()
                                .add(27, 27, 27)
                                .add(jLabel6))
                            .add(layout.createSequentialGroup()
                                .addPreferredGap(org.jdesktop.layout.LayoutStyle.RELATED)
                                .add(jScrollPane1, org.jdesktop.layout.GroupLayout.PREFERRED_SIZE, org.jdesktop.layout.GroupLayout.DEFAULT_SIZE, org.jdesktop.layout.GroupLayout.PREFERRED_SIZE)))
                        .addPreferredGap(org.jdesktop.layout.LayoutStyle.RELATED)
                        .add(layout.createParallelGroup(org.jdesktop.layout.GroupLayout.LEADING)
                            .add(layout.createSequentialGroup()
                                .add(jLabel7)
                                .add(17, 17, 17)
                                .add(jLabel8))
                            .add(layout.createSequentialGroup()
                                .add(cmbGeoFeature, org.jdesktop.layout.GroupLayout.PREFERRED_SIZE, org.jdesktop.layout.GroupLayout.DEFAULT_SIZE, org.jdesktop.layout.GroupLayout.PREFERRED_SIZE)
                                .addPreferredGap(org.jdesktop.layout.LayoutStyle.RELATED)
                                .add(layout.createParallelGroup(org.jdesktop.layout.GroupLayout.BASELINE)
                                    .add(txtPhotoDate, org.jdesktop.layout.GroupLayout.PREFERRED_SIZE, org.jdesktop.layout.GroupLayout.DEFAULT_SIZE, org.jdesktop.layout.GroupLayout.PREFERRED_SIZE)
                                    .add(btnToday))))
                        .addPreferredGap(org.jdesktop.layout.LayoutStyle.RELATED)
                        .add(chkSupplemental)
                        .addPreferredGap(org.jdesktop.layout.LayoutStyle.RELATED)
                        .add(chkCCLicence)
                        .addPreferredGap(org.jdesktop.layout.LayoutStyle.RELATED, 56, Short.MAX_VALUE)
                        .add(layout.createParallelGroup(org.jdesktop.layout.GroupLayout.BASELINE)
                            .add(btnReset)
                            .add(btnUpload))
                        .addPreferredGap(org.jdesktop.layout.LayoutStyle.RELATED))
                    .add(layout.createSequentialGroup()
                        .add(pnlPreview, org.jdesktop.layout.GroupLayout.PREFERRED_SIZE, org.jdesktop.layout.GroupLayout.DEFAULT_SIZE, org.jdesktop.layout.GroupLayout.PREFERRED_SIZE)
                        .add(301, 301, 301)))
                .add(12, 12, 12)
                .add(lblStatus))
        );
        pack();
    }// </editor-fold>//GEN-END:initComponents
    
    
    
    public final void actionPerformed(ActionEvent ae) {
        String action = ae.getActionCommand();
        
        if (action.equals("Image file")) {
            
            DisplayFileChooser();
            
        } else if (action.equals("Today")) {
            
            SetDateToday();
            
        } else if (action.equals("Reset")) {
            
            ResetFields();
            
        } else if (action.equals("Add to queue")) {
            
            ValidateAndAdd();
            
        }
    }
    
    /**
     * Reset everything back to default.
     */
    final public void ResetFields() {
        
//        reset all the fields for restart
        
        acceptFlag = false;
        
        txtImageComments.setText("");
        txtImageTitle.setText("");
        txtImagefile.setText("");
        txtPhotoDate.setText("");
        txtPhotographer.setText("");
        txtSubject.setText("");
        chkSupplemental.setSelected(false);
        chkCCLicence.setSelected(false);
        btnImagefile.grabFocus();
    }
    
    final public void ValidateAndAdd() {
        
//        Validate the details and do the upload
        
        if (txtImagefile.getText().equals("")) {
            Toolkit.getDefaultToolkit().beep();
            JOptionPane.showMessageDialog(this, "No image file specified");
            return;
        }
        
        if (!chkCCLicence.isSelected()) {
            Toolkit.getDefaultToolkit().beep();
            JOptionPane.showMessageDialog(this, "Please check the Creative Commons\nlicence before continuing");
            return;
        }
        
        if (cmbGeoFeature.getSelectedIndex() == 0) {
            Toolkit.getDefaultToolkit().beep();
            JOptionPane.showMessageDialog(this, "No valid category / feature selected");
            return;
        }
        
        if (cmbDirection.getSelectedIndex() == 0) {
            Toolkit.getDefaultToolkit().beep();
            JOptionPane.showMessageDialog(this, "No valid direction selected");
            return;
            
        }
        
        if (txtSubject.getText().trim().equals("")) {
            Toolkit.getDefaultToolkit().beep();
            JOptionPane.showMessageDialog(this, "No grid reference entered");
            return;
        }

        // date should be there and valid (in that order!)
        
        if (txtPhotoDate.getText().trim().equals("")) {
            Toolkit.getDefaultToolkit().beep();
            JOptionPane.showMessageDialog(this, "No date specified for photograph");
            return;
        }        
        
        String enteredDate = txtPhotoDate.getText().trim();
        DateFormat dateFormat = DateFormat.getDateInstance(DateFormat.SHORT, Locale.UK);
        dateFormat.setLenient(false);
        Date actualDate;
        
        try {
            actualDate = dateFormat.parse(enteredDate);
        } catch (ParseException pe) {
            Toolkit.getDefaultToolkit().beep();
            JOptionPane.showMessageDialog(this, "Invalid date specified for photograph\nShould be dd/mm/yyyy");
            return;            
        }
        
        // not in the future?
        
        Date today = new Date();
        if (actualDate.after(today)) {
            Toolkit.getDefaultToolkit().beep();
            JOptionPane.showMessageDialog(this, "Time travel is not allowed. Check your date please!");
            return;
        }

        
        // we have a list of valid gridref regexps...
        
        String subject = new String(txtSubject.getText().trim());
        String photographer = new String(txtPhotographer.getText().trim());
        
        boolean matchSubject = false;
        boolean matchPhotographer = false;
        
        for (int i = 0; i < Main.validGridRefs.length; i++) {
            Pattern gridRegExp = Pattern.compile(Main.validGridRefs[i]);
            Matcher subjectMatch = gridRegExp.matcher(subject);
            if (subjectMatch.find()) {
                matchSubject = true;
            }
            
            if (!photographer.equals("")) {
                Matcher photographerMatch = gridRegExp.matcher(photographer);
                if (photographerMatch.find()) {
                    matchPhotographer = true;
                }
            } else { 
                
                // if there's no hotographer position, it's valid by default
                
                matchPhotographer = true;
            }
        }
        
        if (!matchSubject || !matchPhotographer) {
            Toolkit.getDefaultToolkit().beep();
            JOptionPane.showMessageDialog(this, "Subject or photographer is an invalid grid reference");
            return;            
        }
        
        // decide whether or not we need to resize the image here
        
        
        //TODO: why is this passing the preview sizes?
        
        String submitFileName = txtImagefile.getText();
        String resizeStatus = "original";
        if (Main.doResize) {
            String newFile = ResizeJPGImage(txtImagefile.getText());
            
            // if the resize was sucessful, replace the image name in the list with 
            // the cached resized image otherwise go with the original
            
            if (newFile != null) {
                submitFileName = newFile;
                resizeStatus = "cache";
            }
        }
        
        acceptFlag = true;
        
        // save all the fields
        
        editData[0] = new String(submitFileName);
        editData[1] = new String(txtSubject.getText().trim());
        editData[2] = new String(txtPhotographer.getText().trim());
        editData[3] = new String(cmbDirection.getSelectedItem().toString());
        editData[4] = new String(txtImageTitle.getText().trim());
        editData[5] = new String(txtImageComments.getText());
        editData[6] = new String(cmbGeoFeature.getSelectedItem().toString());
        editData[7] = new String(txtPhotoDate.getText().trim());
        editData[8] = new String(chkSupplemental.isSelected() ? "Y" : "N");
        editData[9] = new String(chkCCLicence.isSelected() ? "Y" : "N");

        // [10] is used to hold the upload status
        editData[10] = new String();
        editData[11] = resizeStatus;
        for (int i = 12; i < 20; i++) {
            editData[i] = new String("");
        }
        
        this.setVisible(false);
        
    }
    
    /**
     * 
     * This routine resizes an image into the cache directory. The resized image will have
     * a major dimension no bigger that 640px with a minor dimension to suit the aspect
     * ratio of the original image
     * @param currentFile Current file name to be resized
     * @return New file name after resizing (includes location)
     */
    final public String ResizeJPGImage(String currentFile) {
        BufferedImage originalPicture = null;
        int fullWidth = 0;
        int fullHeight = 0;
        int scaleWidth = 0;
        int scaleHeight = 0;
        String baseFileName = null;

        try {
            File imageFile = new File(currentFile);
            baseFileName = imageFile.getName();
            originalPicture = ImageIO.read(imageFile);
            fullWidth = originalPicture.getWidth(this);
            fullHeight = originalPicture.getHeight(this);
        } catch (Exception ex) {
            JOptionPane.showMessageDialog(null, "Unable to resize image", "JUploader", JOptionPane.ERROR_MESSAGE);
            return null;
        }
        
        // we need to work out whether our image is landscape or portrait, then
        // decide on the scaled dimensions
        // If it's within acceptable sizes, all we're goping to do is copy it to the
        // cache directory as it (assuming the user rescaled it first but they have
        // rescale switched on
        
        double aspectRatio = (double) fullWidth / (double) fullHeight;
        
        if (fullHeight > fullWidth) {
            // it's portrait, so we enfore a maximum 640px
            scaleHeight = 640;
            scaleWidth = (int) ((double) (scaleHeight / aspectRatio));
        } else {
            // ...landscape
            scaleWidth = 640;
            scaleHeight = (int) ((double) (scaleWidth / aspectRatio));
        }
        
        String newFileName = null;
        try {
            BufferedImage dest = new BufferedImage(scaleWidth, scaleHeight, BufferedImage.TYPE_INT_RGB);
            Graphics2D g = dest.createGraphics();
            AffineTransform at = AffineTransform.getScaleInstance((double)scaleWidth / originalPicture.getWidth(),
                    (double)scaleHeight / originalPicture.getHeight());
            g.drawRenderedImage(originalPicture, at);

            newFileName = Main.cacheDirectory + "/" + baseFileName;

            ImageIO.write(dest,"JPG", new File(newFileName));
            BufferedImage newPicture = new BufferedImage(scaleWidth, scaleHeight, BufferedImage.TYPE_INT_RGB);
            newPicture.createGraphics().drawImage(originalPicture, 0, 0, null);
            
        } catch (IOException ex) {
            
            Toolkit.getDefaultToolkit().beep();
            JOptionPane.showMessageDialog(this, "Unable to resize image\nFile system error");
            return null;
            
        }
//        String newFileName = Main.cacheDirectory + "/" + baseFileName;
//        try {
//            FileOutputStream fos = new FileOutputStream(newFileName);
//            ImageIO.write(newPicture, "jpg", fos);
//            fos.close();
//        } catch (Exception ex) {
//            Toolkit.getDefaultToolkit().beep();
//            JOptionPane.showMessageDialog(this, "Unable to resize image\nFile system error");
//            return null;
//        }
        JOptionPane.showMessageDialog(this, "Image resized");
        return newFileName;
    }
    
    /**
     * Put todays date into the photograph date field.
     */
    final public void SetDateToday() {
        // set today's date
        
        String today = DateFormat.getDateInstance(DateFormat.SHORT, Locale.UK).format(new Date());
        txtPhotoDate.setText(today);
    }
    
    /**
     * Display the file selector dialog
     */
    final public void DisplayFileChooser() {
//        Display a file chooser dialog for a jpg file
        JFileChooser fc;
        
        // if there's a non-null last directory, get the fill chooser to go there
        
        if (!Main.lastDirectory.equals("")) {
            fc = new JFileChooser(Main.lastDirectory);
        } else {
            fc = new JFileChooser();
        }
        
        // create the jpg chooser and attach it...
        
        fc.addChoosableFileFilter(new JPEGFileFilter());
        
        int rc = fc.showOpenDialog(this);
        if (rc == JFileChooser.APPROVE_OPTION) {
            
            String filePath = fc.getSelectedFile().getPath();
            txtImagefile.setText(filePath);
            preview.setFilename(filePath);
            Main.lastDirectory = fc.getSelectedFile().getParent();
            
            
            // if the file name looks like a grid reference, we'll fill in the grid
            // ref as well and default the photographer position (someone will like it!)
            
            String fileName = fc.getSelectedFile().getName();
            String gridRef = null;
            Pattern ukGridRef = Pattern.compile("([A-Z]|[a-z]){2}\\d{4,6}");
            Matcher ukMatcher = ukGridRef.matcher(fileName);
            if (ukMatcher.find()) {
                int start = ukMatcher.start();
                int end = ukMatcher.end();
                gridRef = fileName.substring(start, end);
            } else {
                
                // could it be Irish?
                
                Pattern irGridRef = Pattern.compile("([A-Z]|[a-z]){1}\\d{4}");
                Matcher irMatcher = irGridRef.matcher(fileName);
                if (irMatcher.find()) {
                    int start = irMatcher.start();
                    int end = irMatcher.end();
                    gridRef = fileName.substring(start, end);
                }
                
            }
            
            // if the user has disabled the gridrefFromImage flag in properties, we
            // don't bother with the assignment. Many cameras generate file names that
            // can confuse the issue
            
            if ((gridRef != null) && Main.gridrefFromImage) {
                txtSubject.setText(gridRef);
                txtPhotographer.setText(gridRef);
            }
            
        } else {
            txtImagefile.setText("");
            
        }
        
        
        
        
    }
    
    class JPEGFileFilter extends FileFilter {
        public boolean accept(File f) {
            
            if (f.isDirectory()) return true;
            
            if (f.getAbsolutePath().toLowerCase().endsWith(".jpg")) {
                return true;
            } else {
                return false;
            }
        }
        
        public String getDescription() {return "*.jpg | JPG image files";}
    }
    
    class ImagePreview extends javax.swing.JComponent implements ImageObserver {
        
        String filename = new String();
        
        public void setFilename(String fn) {
            filename = fn;
            this.repaint();
        }
        
        public void paint(Graphics g) {
            
            if (filename.equals("")) {
                return;
            }
            
            Graphics2D g2d = (Graphics2D) g;
            
            Image img = Toolkit.getDefaultToolkit().getImage(filename);
            
            
            g.drawImage(img, 0, 0, this.getWidth(), this.getHeight(), this);
            
            
        }
        
        
        public boolean imageUpdate(Image img, int infoflags, int x, int y, int width, int height) {
            if ((infoflags & ImageObserver.ALLBITS) == ImageObserver.ALLBITS) {
                
                if (img.getHeight(this) * img.getWidth(this) > 307200) {
                    lblStatus.setText("Warning: image will have to be resized");
                    if (Main.doResize) {
                        lblStatus.setBackground(Color.YELLOW);
                        lblStatus.setOpaque(true);
                        lblStatus.setText("Image resize will be attempted locally");
                    } else {
                        lblStatus.setBackground(Color.RED);
                        lblStatus.setOpaque(true);
                        lblStatus.setText("Server will resize image. Consider using local resize");
                        
                    }
                } else {
                    lblStatus.setText("");
                    lblStatus.setOpaque(false);
                }
                
                this.repaint();
                return false;
            } else {
                return true;
            }
        }
    }
    
    
    
    // Variables declaration - do not modify//GEN-BEGIN:variables
    private javax.swing.JButton btnImagefile;
    private javax.swing.JButton btnReset;
    private javax.swing.JButton btnToday;
    private javax.swing.JButton btnUpload;
    private javax.swing.JCheckBox chkCCLicence;
    private javax.swing.JCheckBox chkSupplemental;
    private javax.swing.JComboBox cmbDirection;
    private javax.swing.JComboBox cmbGeoFeature;
    private javax.swing.JLabel jLabel2;
    private javax.swing.JLabel jLabel3;
    private javax.swing.JLabel jLabel4;
    private javax.swing.JLabel jLabel5;
    private javax.swing.JLabel jLabel6;
    private javax.swing.JLabel jLabel7;
    private javax.swing.JLabel jLabel8;
    private javax.swing.JScrollPane jScrollPane1;
    private javax.swing.JLabel lblStatus;
    private javax.swing.JPanel pnlCompass;
    private javax.swing.JPanel pnlPreview;
    private javax.swing.JTextArea txtImageComments;
    private javax.swing.JTextField txtImageTitle;
    private javax.swing.JTextField txtImagefile;
    private javax.swing.JTextField txtPhotoDate;
    private javax.swing.JTextField txtPhotographer;
    private javax.swing.JTextField txtSubject;
    // End of variables declaration//GEN-END:variables
    
}
