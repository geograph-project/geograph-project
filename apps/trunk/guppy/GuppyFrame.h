#pragma once

class GuppyFrame :
	public wxFrame
{
public:
    // ctor(s)
    GuppyFrame(const wxString& title);
	~GuppyFrame(void);

    // event handlers (these functions should _not_ be virtual)
    void OnQuit(wxCommandEvent& event);
    void OnAbout(wxCommandEvent& event);

private:
    // any class wishing to process wxWidgets events must use this macro
    DECLARE_EVENT_TABLE()

};
