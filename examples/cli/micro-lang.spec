## Whitespace characters to be ignored (BOM handling and EOL detection must be done on Filestream level)
__WHITESPACE: (#009|#010|#013|#032)+

## Comment starting points, that's where another scanner/parser especially for comments starts
COMMENT_START: ((//)|(/[*]))

## Floats with Exponent
FLOAT: ([0-9]*)([.]?)([0-9]+)(([eE]([-+]?)([0-9]+))?)

## Numbers
NUM: ([0-9]+)

## (Escaped) Strings
STRING: "(([^"\]|(\")|(\\))*)"

## IDs
ID: [a-zA-Z_]([a-zA-Z_0-9]*)
