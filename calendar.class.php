<?

class calendar {
  var $date;

  function calendar($date) {
    $this->date = $date;
  }

  function display() {
    global $PHP_SELF;

    ### Extract information from the given date:
    $month  = date("n", $this->date);
    $year = date("Y", $this->date);
    $day = date("d", $this->date);

    ### Extract first day of the month:
    $first = date("w", mktime(0, 0, 0, $month, 1, $year));
        
    ### Extract last day of the month:
    $last = date("t", mktime(0, 0, 0, $month, 1, $year));

    ### Calculate previous and next months dates:
    $prev = mktime(0, 0, 0, $month - 1, $day, $year);
    $next = mktime(0, 0, 0, $month + 1, $day, $year);

    ### Generate calendar header:
    $output .= "\n<!-- calendar -->\n";
    $output .= "<TABLE WIDTH=\"100%\" BORDER=\"1\" CELLSPACING=\"0\" CELLPADDING=\"1\">\n";
    $output .= " <TR><TD ALIGN=\"center\" COLSPAN=\"7\"><SMALL><A HREF=\"$PHP_SELF?date=$prev\">&lt;&lt;</A> &nbsp; ". date("F Y", $this->date) ." &nbsp; <A HREF=\"$PHP_SELF?date=$next\">&gt;&gt;</A></SMALL></TD></TR>\n";
    $output .= " <TR><TD ALIGN=\"center\"><SMALL>S</SMALL></TD><TD ALIGN=\"center\"><SMALL>M</SMALL></TD><TD ALIGN=\"center\"><SMALL>T</SMALL></TD><TD ALIGN=\"center\"><SMALL>W</SMALL></TD><TD ALIGN=\"center\"><SMALL>T</SMALL></TD><TD ALIGN=\"center\"><SMALL>F</SMALL></TD><TD ALIGN=\"center\"><SMALL>S</SMALL></TD></TR>\n";
 
    ### Initialize temporary variables:
    $nday = 1;
    $sday = $first;
   
    ### Loop through all the days of the month:
    while ($nday <= $last) {
      ### Set up blank days for first week of the month:
      if ($first) {
        $output .= " <TR><TD COLSPAN=\"$first\">&nbsp</TD>\n";
        $first = 0;
      }
        
      ### Start every week on a new line:
      if ($sday == 0) $output .=  " <TR>\n";
    
      ### Print one cell:
      $date = mktime(0, 0, 0, $month, $nday, $year);
      if ($nday == $day) $output .= "  <TD ALIGN=\"center\"><SMALL><B>$nday</B></SMALL></TD>\n";
      else if ($date > time()) $output .= "  <TD ALIGN=\"center\"><SMALL>$nday</SMALL></TD>\n";
      else $output .= "  <TD ALIGN=\"center\"><SMALL><A HREF=\"$PHP_SELF?date=$date\" STYLE=\"text-decoration: none;\">$nday</A></SMALL></TD>\n";
     
      ### Start every week on a new line:
      if ($sday == 6) $output .=  " </TR>\n";
        
      ### Update temporary variables:
      $sday++;
      $sday = $sday % 7;
      $nday++;
    }
    
    ### Finish the calendar:
    if ($sday != 0) {
      $end = 7 - $sday;
      $output .= "  <TD COLSPAN=\"$end\">&nbsp;</TD>\n </TR>\n";
    }
    $output .= "</TABLE>\n\n";

    ### Return calendar:
    return $output;
  }
}

?>
