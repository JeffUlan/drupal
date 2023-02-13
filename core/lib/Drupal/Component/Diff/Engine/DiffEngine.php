<?php

namespace Drupal\Component\Diff\Engine;

/**
 * Class used internally by Diff to actually compute the diffs.
 *
 * The algorithm used here is mostly lifted from the perl module
 * Algorithm::Diff (version 1.06) by Ned Konz, which is available at:
 *   http://www.perl.com/CPAN/authors/id/N/NE/NEDKONZ/Algorithm-Diff-1.06.zip
 *
 * More ideas are taken from:
 *   http://www.ics.uci.edu/~eppstein/161/960229.html
 *
 * Some ideas (and a bit of code) are from analyze.c, from GNU
 * diffutils-2.7, which can be found at:
 *   ftp://gnudist.gnu.org/pub/gnu/diffutils/diffutils-2.7.tar.gz
 *
 * closingly, some ideas (subdivision by NCHUNKS > 2, and some optimizations)
 * are my own.
 *
 * Line length limits for robustness added by Tim Starling, 2005-08-31
 *
 * @author Geoffrey T. Dairiki, Tim Starling
 * @private
 * @subpackage DifferenceEngine
 *
 * @deprecated in drupal:10.1.0 and is removed from drupal:11.0.0. Use
 *   sebastianbergmann/diff instead.
 *
 * @see https://www.drupal.org/node/3337942
 */
#[\AllowDynamicProperties]
class DiffEngine {

  const USE_ASSERTS = FALSE;

  const MAX_XREF_LENGTH = 10000;

  public function __construct() {
    @trigger_error('Drupal\Component\Diff\Engine\DiffEngine is deprecated in drupal:10.1.0 and is removed from drupal:11.0.0. Use sebastianbergmann/diff instead. See https://www.drupal.org/node/3337942', E_USER_DEPRECATED);
  }

  public function diff($from_lines, $to_lines) {

    $n_from = sizeof($from_lines);
    $n_to = sizeof($to_lines);

    $this->xchanged = $this->ychanged = [];
    $this->xv = $this->yv = [];
    $this->xind = $this->yind = [];
    unset($this->seq);
    unset($this->in_seq);
    unset($this->lcs);

    // Skip leading common lines.
    for ($skip = 0; $skip < $n_from && $skip < $n_to; $skip++) {
      if ($from_lines[$skip] !== $to_lines[$skip]) {
        break;
      }
      $this->xchanged[$skip] = $this->ychanged[$skip] = FALSE;
    }
    // Skip trailing common lines.
    $xi = $n_from;
    $yi = $n_to;
    for ($endskip = 0; --$xi > $skip && --$yi > $skip; $endskip++) {
      if ($from_lines[$xi] !== $to_lines[$yi]) {
        break;
      }
      $this->xchanged[$xi] = $this->ychanged[$yi] = FALSE;
    }

    // Ignore lines which do not exist in both files.
    for ($xi = $skip; $xi < $n_from - $endskip; $xi++) {
      $xhash[$this->_line_hash($from_lines[$xi])] = 1;
    }

    for ($yi = $skip; $yi < $n_to - $endskip; $yi++) {
      $line = $to_lines[$yi];
      if ($this->ychanged[$yi] = empty($xhash[$this->_line_hash($line)])) {
        continue;
      }
      $yhash[$this->_line_hash($line)] = 1;
      $this->yv[] = $line;
      $this->yind[] = $yi;
    }
    for ($xi = $skip; $xi < $n_from - $endskip; $xi++) {
      $line = $from_lines[$xi];
      if ($this->xchanged[$xi] = empty($yhash[$this->_line_hash($line)])) {
        continue;
      }
      $this->xv[] = $line;
      $this->xind[] = $xi;
    }

    // Find the LCS.
    $this->_compareseq(0, sizeof($this->xv), 0, sizeof($this->yv));

    // Merge edits when possible
    $this->_shift_boundaries($from_lines, $this->xchanged, $this->ychanged);
    $this->_shift_boundaries($to_lines, $this->ychanged, $this->xchanged);

    // Compute the edit operations.
    $edits = [];
    $xi = $yi = 0;
    while ($xi < $n_from || $yi < $n_to) {
      $this::USE_ASSERTS && assert($yi < $n_to || $this->xchanged[$xi]);
      $this::USE_ASSERTS && assert($xi < $n_from || $this->ychanged[$yi]);

      // Skip matching "snake".
      $copy = [];
      while ( $xi < $n_from && $yi < $n_to && !$this->xchanged[$xi] && !$this->ychanged[$yi]) {
        $copy[] = $from_lines[$xi++];
        ++$yi;
      }
      if ($copy) {
        $edits[] = new DiffOpCopy($copy);
      }
      // Find deletes & adds.
      $delete = [];
      while ($xi < $n_from && $this->xchanged[$xi]) {
        $delete[] = $from_lines[$xi++];
      }
      $add = [];
      while ($yi < $n_to && $this->ychanged[$yi]) {
        $add[] = $to_lines[$yi++];
      }
      if ($delete && $add) {
        $edits[] = new DiffOpChange($delete, $add);
      }
      elseif ($delete) {
        $edits[] = new DiffOpDelete($delete);
      }
      elseif ($add) {
        $edits[] = new DiffOpAdd($add);
      }
    }
    return $edits;
  }

  /**
   * Returns the whole line if it's small enough, or a hash otherwise.
   */
  protected function _line_hash($line) {
    if (mb_strlen($line) > $this::MAX_XREF_LENGTH) {
      return hash('crc32b', $line);
    }
    else {
      return $line;
    }
  }


  /**
   * Divide the Largest Common Subsequence (LCS) of the sequences
   * [XOFF, XLIM) and [YOFF, YLIM) into NCHUNKS approximately equally
   * sized segments.
   *
   * Returns (LCS, PTS).  LCS is the length of the LCS. PTS is an
   * array of NCHUNKS+1 (X, Y) indexes giving the diving points between
   * sub sequences.  The first sub-sequence is contained in [X0, X1),
   * [Y0, Y1), the second in [X1, X2), [Y1, Y2) and so on.  Note
   * that (X0, Y0) == (XOFF, YOFF) and
   * (X[NCHUNKS], Y[NCHUNKS]) == (XLIM, YLIM).
   *
   * This function assumes that the first lines of the specified portions
   * of the two files do not match, and likewise that the last lines do not
   * match.  The caller must trim matching lines from the beginning and end
   * of the portions it is going to specify.
   */
  protected function _diag($xoff, $xlim, $yoff, $ylim, $nchunks) {
    $flip = FALSE;

    if ($xlim - $xoff > $ylim - $yoff) {
      // Things seems faster (I'm not sure I understand why)
      // when the shortest sequence in X.
      $flip = TRUE;
      [$xoff, $xlim, $yoff, $ylim] = [$yoff, $ylim, $xoff, $xlim];
    }

    if ($flip) {
      for ($i = $ylim - 1; $i >= $yoff; $i--) {
        $ymatches[$this->xv[$i]][] = $i;
      }
    }
    else {
      for ($i = $ylim - 1; $i >= $yoff; $i--) {
        $ymatches[$this->yv[$i]][] = $i;
      }
    }
    $this->lcs = 0;
    $this->seq[0] = $yoff - 1;
    $this->in_seq = [];
    $ymids[0] = [];

    $numer = $xlim - $xoff + $nchunks - 1;
    $x = $xoff;
    for ($chunk = 0; $chunk < $nchunks; $chunk++) {
      if ($chunk > 0) {
        for ($i = 0; $i <= $this->lcs; $i++) {
          $ymids[$i][$chunk - 1] = $this->seq[$i];
        }
      }

      $x1 = $xoff + (int)(($numer + ($xlim - $xoff) * $chunk) / $nchunks);
      for (; $x < $x1; $x++) {
        $line = $flip ? $this->yv[$x] : $this->xv[$x];
        if (empty($ymatches[$line])) {
          continue;
        }
        $matches = $ymatches[$line];
        $found_empty = FALSE;
        foreach ($matches as $y) {
          if (!$found_empty) {
            if (empty($this->in_seq[$y])) {
              $k = $this->_lcs_pos($y);
              $this::USE_ASSERTS && assert($k > 0);
              $ymids[$k] = $ymids[$k - 1];
              $found_empty = TRUE;
            }
          }
          else {
            if ($y > $this->seq[$k - 1]) {
              $this::USE_ASSERTS && assert($y < $this->seq[$k]);
              // Optimization: this is a common case:
              // next match is just replacing previous match.
              $this->in_seq[$this->seq[$k]] = FALSE;
              $this->seq[$k] = $y;
              $this->in_seq[$y] = 1;
            }
            elseif (empty($this->in_seq[$y])) {
              $k = $this->_lcs_pos($y);
              $this::USE_ASSERTS && assert($k > 0);
              $ymids[$k] = $ymids[$k - 1];
            }
          }
        }
      }
    }

    $seps[] = $flip ? [$yoff, $xoff] : [$xoff, $yoff];
    $ymid = $ymids[$this->lcs];
    for ($n = 0; $n < $nchunks - 1; $n++) {
      $x1 = $xoff + (int)(($numer + ($xlim - $xoff) * $n) / $nchunks);
      $y1 = $ymid[$n] + 1;
      $seps[] = $flip ? [$y1, $x1] : [$x1, $y1];
    }
    $seps[] = $flip ? [$ylim, $xlim] : [$xlim, $ylim];

    return [$this->lcs, $seps];
  }

  protected function _lcs_pos($ypos) {

    $end = $this->lcs;
    if ($end == 0 || $ypos > $this->seq[$end]) {
      $this->seq[++$this->lcs] = $ypos;
      $this->in_seq[$ypos] = 1;
      return $this->lcs;
    }

    $beg = 1;
    while ($beg < $end) {
      $mid = (int)(($beg + $end) / 2);
      if ($ypos > $this->seq[$mid]) {
        $beg = $mid + 1;
      }
      else {
        $end = $mid;
      }
    }

    $this::USE_ASSERTS && assert($ypos != $this->seq[$end]);

    $this->in_seq[$this->seq[$end]] = FALSE;
    $this->seq[$end] = $ypos;
    $this->in_seq[$ypos] = 1;
    return $end;
  }

  /**
   * Find LCS of two sequences.
   *
   * The results are recorded in the vectors $this->{x,y}changed[], by
   * storing a 1 in the element for each line that is an insertion
   * or deletion (ie. is not in the LCS).
   *
   * The subsequence of file 0 is [XOFF, XLIM) and likewise for file 1.
   *
   * Note that XLIM, YLIM are exclusive bounds.
   * All line numbers are origin-0 and discarded lines are not counted.
   */
  protected function _compareseq($xoff, $xlim, $yoff, $ylim) {

    // Slide down the bottom initial diagonal.
    while ($xoff < $xlim && $yoff < $ylim && $this->xv[$xoff] == $this->yv[$yoff]) {
      ++$xoff;
      ++$yoff;
    }

    // Slide up the top initial diagonal.
    while ($xlim > $xoff && $ylim > $yoff && $this->xv[$xlim - 1] == $this->yv[$ylim - 1]) {
      --$xlim;
      --$ylim;
    }

    if ($xoff == $xlim || $yoff == $ylim) {
      $lcs = 0;
    }
    else {
      // This is ad hoc but seems to work well.
      //$nchunks = sqrt(min($xlim - $xoff, $ylim - $yoff) / 2.5);
      //$nchunks = max(2, min(8, (int)$nchunks));
      $nchunks = min(7, $xlim - $xoff, $ylim - $yoff) + 1;
      [$lcs, $seps] = $this->_diag($xoff, $xlim, $yoff, $ylim, $nchunks);
    }

    if ($lcs == 0) {
      // X and Y sequences have no common subsequence:
      // mark all changed.
      while ($yoff < $ylim) {
        $this->ychanged[$this->yind[$yoff++]] = 1;
      }
      while ($xoff < $xlim) {
        $this->xchanged[$this->xind[$xoff++]] = 1;
      }
    }
    else {
      // Use the partitions to split this problem into subproblems.
      reset($seps);
      $pt1 = $seps[0];
      while ($pt2 = next($seps)) {
        $this->_compareseq ($pt1[0], $pt2[0], $pt1[1], $pt2[1]);
        $pt1 = $pt2;
      }
    }
  }

  /**
   * Adjust inserts/deletes of identical lines to join changes
   * as much as possible.
   *
   * We do something when a run of changed lines include a
   * line at one end and has an excluded, identical line at the other.
   * We are free to choose which identical line is included.
   * `compareseq' usually chooses the one at the beginning,
   * but usually it is cleaner to consider the following identical line
   * to be the "change".
   *
   * This is extracted verbatim from analyze.c (GNU diffutils-2.7).
   */
  protected function _shift_boundaries($lines, &$changed, $other_changed) {
    $i = 0;
    $j = 0;

    $this::USE_ASSERTS && assert(sizeof($lines) == sizeof($changed));
    $len = sizeof($lines);
    $other_len = sizeof($other_changed);

    while (1) {
      /*
       * Scan forwards to find beginning of another run of changes.
       * Also keep track of the corresponding point in the other file.
       *
       * Throughout this code, $i and $j are adjusted together so that
       * the first $i elements of $changed and the first $j elements
       * of $other_changed both contain the same number of zeros
       * (unchanged lines).
       * Furthermore, $j is always kept so that $j == $other_len or
       * $other_changed[$j] == FALSE.
       */
      while ($j < $other_len && $other_changed[$j]) {
        $j++;
      }
      while ($i < $len && !$changed[$i]) {
        $this::USE_ASSERTS && assert($j < $other_len && ! $other_changed[$j]);
        $i++;
        $j++;
        while ($j < $other_len && $other_changed[$j]) {
          $j++;
        }
      }

      if ($i == $len) {
        break;
      }
      $start = $i;

      // Find the end of this run of changes.
      while (++$i < $len && $changed[$i]) {
        continue;
      }

      do {
        /*
         * Record the length of this run of changes, so that
         * we can later determine whether the run has grown.
         */
        $runlength = $i - $start;

        /*
         * Move the changed region back, so long as the
         * previous unchanged line matches the last changed one.
         * This merges with previous changed regions.
         */
        while ($start > 0 && $lines[$start - 1] == $lines[$i - 1]) {
          $changed[--$start] = 1;
          $changed[--$i] = FALSE;
          while ($start > 0 && $changed[$start - 1]) {
            $start--;
          }
          $this::USE_ASSERTS && assert($j > 0);
          while ($other_changed[--$j]) {
            continue;
          }
          $this::USE_ASSERTS && assert($j >= 0 && !$other_changed[$j]);
        }

        /*
         * Set CORRESPONDING to the end of the changed run, at the last
         * point where it corresponds to a changed run in the other file.
         * CORRESPONDING == LEN means no such point has been found.
         */
        $corresponding = $j < $other_len ? $i : $len;

        /*
         * Move the changed region forward, so long as the
         * first changed line matches the following unchanged one.
         * This merges with following changed regions.
         * Do this second, so that if there are no merges,
         * the changed region is moved forward as far as possible.
         */
        while ($i < $len && $lines[$start] == $lines[$i]) {
          $changed[$start++] = FALSE;
          $changed[$i++] = 1;
          while ($i < $len && $changed[$i]) {
            $i++;
          }
          $this::USE_ASSERTS && assert($j < $other_len && ! $other_changed[$j]);
          $j++;
          if ($j < $other_len && $other_changed[$j]) {
            $corresponding = $i;
            while ($j < $other_len && $other_changed[$j]) {
              $j++;
            }
          }
        }
      } while ($runlength != $i - $start);

      /*
       * If possible, move the fully-merged run of changes
       * back to a corresponding run in the other file.
       */
      while ($corresponding < $i) {
        $changed[--$start] = 1;
        $changed[--$i] = 0;
        $this::USE_ASSERTS && assert($j > 0);
        while ($other_changed[--$j]) {
          continue;
        }
        $this::USE_ASSERTS && assert($j >= 0 && !$other_changed[$j]);
      }
    }
  }

}
