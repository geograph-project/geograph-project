{dynamic}

This is a multi-part message in MIME format.

------=_NextPart_000_00DF_01C5EB66.9313FF40
Content-Type: text/plain;
	charset="Windows-1252"
Content-Transfer-Encoding: quoted-printable

--This message was sent through the {$http_host} web site--
   
{$msg}

--------------------------------

http://{$http_host}/photo/{$image->gridimage_id}

{$image->title|escape:'html'}
{$image->comment|escape:'html'}

View Online at http://{$http_host}/photo/{$image->gridimage_id}
--------------------------------
Image =A9 Copyright {$image->realname} and licensed for reuse under this Creative Commons Licence. 
http://creativecommons.org/licenses/by-sa/2.0/
--------------------------------

This message was sent to you by site visitor to Geograph Britain and Ireland,
forward abuse complaints to: rogersgm@gmail.com

------=_NextPart_000_00DF_01C5EB66.9313FF40
Content-Type: text/html;
	charset="Windows-1252"
Content-Transfer-Encoding: quoted-printable

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<HTML><HEAD><TITLE></TITLE>
<META http-equiv=3DContent-Type content=3D"text/html; =
charset=3Dwindows-1252"><BASE=20
href=3D"http://{$http_host}/"\>
<META content=3D"Geograph User: {$user->user_id}" name=3DGENERATOR>
</HEAD>
<BODY bgColor=3D#eeeeff leftMargin=3D0 topMargin=3D0 MARGINHEIGHT=3D"0" =
MARGINWIDTH=3D"0">
<TABLE cellSpacing=3D0 cellPadding=3D0 width=3D"750" align=3Dcenter style=3D"width:750px;">
<TBODY><TR><TD>
<TABLE cellSpacing=3D0 cellPadding=3D4 width=3D"100%">
  <TBODY>
  <TR>
    <TD bgColor=3D#000066>&nbsp;</TD>
    <TD bgColor=3D#000066><A href=3D"http://{$http_host}/"><IMG =
height=3D74=20
      src=3D"http://{$http_host}/templates/basic/img/logo.gif" width=3D257 =
border=3D0></A></TD>
    <TD vAlign=3Dtop align=3Dcenter bgColor=3D#000066><A=20
      href=3D"http://{$http_host}/"><FONT face=3DGeorgia =
color=3D#ffffff=20
      size=3D+2>{$http_host}</FONT></A><BR><FONT face=3DGeorgia =
color=3D#ffffff><I>The Geograph Britain and Ireland project aims to collect geographically representative<BR> photographs of every square kilometre of the British Isles and you can be part of it.</I></FONT></TD>
    <TD bgColor=3D#000066>&nbsp;</TD></TR>
  <TR>
    <TD>&nbsp;</TD>
    <TD COLSPAN="2" ALIGN="CENTER">
      </TD>
    <TD>&nbsp;</TD>
    </TR>
  <TR>
    <TD>&nbsp;</TD>
    <TD style=3D"BORDER-TOP: black 1px solid; BORDER-LEFT: black 1px =
solid"=20
    vAlign=3Dtop bgColor=3D#ffffff><BR><FONT face=3DGeorgia>
    {$htmlmsg}
    </FONT><BR></TD>
    <TD style=3D"BORDER-RIGHT: black 1px solid; BORDER-TOP: black 1px =
solid"=20
    align=3Dmiddle bgColor=3D#ffffff><BR><A=20
      href=3D"http://{$http_host}/photo/{$image->gridimage_id}">{$image->getFull(true)|replace:'=':'=3D'|replace:'alt=':'border=3D0 alt='}</A></TD>
    <TD>&nbsp;</TD></TR>
  <TR>
    <TD>&nbsp;</TD>
    <TD style=3D"BORDER-LEFT: black 1px solid" vAlign=3Dtop=20
    bgColor=3D#ffffff>&nbsp;</TD>
    <TD style=3D"BORDER-RIGHT: black 1px solid" align=3Dmiddle =
bgColor=3D#ffffff>
      <DIV class=3Dcaption><FONT face=3DGeorgia size=3D-1><B>{$image->title|escape:'html'}</B></FONT></DIV>
      <DIV class=3Dcaption><FONT face=3DGeorgia size=3D-1>{$image->comment|escape:'html'|geographlinks}</FONT>=
</DIV></TD>
    <TD>&nbsp;</TD></TR>
  <TR>
    <TD>&nbsp;</TD>
    <TD=20
    style=3D"BORDER-RIGHT: black 1px solid; BORDER-LEFT: black 1px =
solid; BORDER-BOTTOM: black 1px solid"=20
    vAlign=3Dtop align=3Dmiddle bgColor=3D#ffffff colSpan=3D2><FONT =
face=3DGeorgia>View=20
      Online at <A=20
      =
href=3D"http://{$http_host}/photo/{$image->gridimage_id}">http://{$http_host}/photo/{$image->gridimage_id}</A></FONT></TD>
    <TD>&nbsp;</TD></TR>
  <TR>
    <TD>&nbsp;</TD>
    <TD vAlign=3Dtop align=3Dmiddle bgColor=3D#dddddd colSpan=3D2><A=20
      href=3D"http://creativecommons.org/licenses/by-sa/2.0/"><IMG =
height=3D31=20
      src=3D"http://creativecommons.org/images/public/somerights20.gif" width=3D88 =
align=3Dright=20
      border=3D0></A> <FONT face=3DGeorgia>Image =A9 Copyright <A =
title=3D"View profile"=20
      href=3D"http://{$http_host}{$image->profile_link|replace:'=':'=3D'}">{$image->realname}</A> and licensed for =
reuse under this=20
      <A class=3Dnowrap =
href=3D"http://creativecommons.org/licenses/by-sa/2.0/"=20
      rel=3Dlicense>Creative&nbsp;Commons&nbsp;Licence</A>.</FONT></TD>
    <TD>&nbsp;</TD></TR>
</TBODY></TABLE>
</TD></TR></TBODY></TABLE>
<P align=3Dcenter><FONT face=3DGeorgia size=3D-1>This message was sent =
to you by site=20
visitor to Geograph Britain and Ireland, <BR>forward abuse complaints to:=20
rogersgm@gmail.com</FONT><!-- {$user->user_id} --></P></BODY></HTML>

------=_NextPart_000_00DF_01C5EB66.9313FF40--

{/dynamic}