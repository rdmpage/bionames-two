
li.closed {
  list-style: none;         /* remove default bullet */
  position: relative;
  padding-left: 1.2em;      /* space reserved for marker */
}

li.closed::before {
  content: "◯";
  position: absolute;
  left: 0;
  top: 0.2em;               /* tweak vertical alignment */
}

li.open {
  list-style: none;         /* remove default bullet */
  position: relative;
  padding-left: 1.2em;      /* space reserved for marker */
}

li.open::before {
  content: "●";
  position: absolute;
  left: 0;
  top: 0.2em;               /* tweak vertical alignment */
}

