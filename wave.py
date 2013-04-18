#Name: Wave
#Info: make the Zlevel move in a sinus. CAUTION: maybe you fan or other parts will collide with the object!
#Depend: GCode
#Type: postprocess
#Param: difference(float:15) Wave difference / amplitude(mm)
#Param: waves(float:4) Total waves
#Param: fromLayer(float:4) Start effect from (layer nr)
#Param: layerheight(float:0.2) Layerheight
#Param: centerX(float:102.5) Center of function X(mm)
#Param: centerY(float:102.5) Center of function Y(mm)

# SPECIAL THANKS TO: Jelle, Hendrik and jeremie [http://betterprinter.blogspot.fr/2013/02/how-tun-run-python-cura-plugin-without.html]

# see my projects on:
# www.rooiejoris.nl
# www.facebook.com/europerminutedesign
# www.thingiverse.com/joris


import re, math

############ BEGIN CURA PLUGIN STAND-ALONIFICATION ############
# This part is an "adapter" to Daid's version of my original Cura/Skeinforge plugin that
# he upgraded to the lastest & simpler Cura plugin system. It enables commmand-line
# postprocessing of a gcode file, so as to insert the temperature commands at each layer.
#
# Also it is still viewed by Cura as a regular and valid plugin!
#
# To run it you need Python, then simply run it like
#   wood_standalone.py --min minTemp --max maxTemp --grain grainSize --file gcodeFile
# It will "patch" your gcode file with the appropriate M104 temperature change.
#
import inspect
import sys
import getopt

zScale = 1
startEffect = 0

def plugin_standalone_usage(myName):
 print "Usage:"
 print "  "+myName+" -d difference -x centerx -y centery -w waves -l layerheight -n fromlayer -f gcodeFile"
 print "Standalone usage Licensed under CC-BY-NC from Jeremie.Francois@gmail.com (betterprinter.blogspot.com)"
 print "Wave script Licensed under CC-BY-NC from www.rooiejoris.nl"
 sys.exit()

try:
 filename
except NameError:
 # Then we are called from the command line (not from cura)
 # trying len(inspect.stack()) > 2 would be less secure btw
 opts, extraparams = getopt.getopt(sys.argv[1:],'d:x:y:w:l:n:f',['difference=','centerx=','centery=','waves=','layerheight=','fromlayer=','file=']) 
# amplitude = 4
 difference = 40
 waves = 4
 centerX = 105
 centerY = 105
 layerheight = 0.2
 fromLayer = 5;

 filename="test.g"

 for o,p in opts:
  if o in ['-d','--difference']:
   difference = float(p)
  elif o in ['-x','--centerx']:
   centerX = float(p)
  elif o in ['-y','--centery']:
   centerY = float(p)
  elif o in ['-w','--waves']:
   waves = float(p)
  elif o in ['-l','--layerheight']:
   layerheight = float(p)
  elif o in ['-n','--fromlayer']:
   layerheight = float(p)
  elif o in ['-f','--file']:
   filename = p
#   filename = 'test.g'
 if not filename:
  plugin_standalone_usage(inspect.stack()[0][1])
#  plugin_standalone_usage('test.g')
#
############ END CURA PLUGIN STAND-ALONIFICATION ############




def getValue(line, key, default = None):
       if not key in line or (';' in line and line.find(key) > line.find(';')):
               return default
       subPart = line[line.find(key) + 1:]
       m = re.search('^[0-9]+\.?[0-9]*', subPart)
       if m == None:
               return default
       try:
               return float(m.group(0))
       except:
               return default

               
               
with open(filename, "r") as f:
       lines = f.readlines()

z = 0
x = 0
y = 0
e = 0
v = 0
maxZ = 0.1

layer = 0
#persistentZ = 0



with open(filename, "w") as f:
#       print("binnen")

       for line in lines:
               if getValue(line, 'G', None) == 1:
                       maxZ = getValue(line, "Z", maxZ) 


       for line in lines:
#               print("binnen")
               if ";LAYER:" in line:
#                    print("binnen")
                    layer = line[7:]
#                    print(layer)
               if layer > fromLayer:
                   startEffect = 1
               
               e = getValue(line, "E", e)        
               z = getValue(line, "Z", z)
#               if getValue(line, "G", None) == 1 and e: #only do something with G1 commands
               if getValue(line, "G", None) == 1 and startEffect == 1:# and e: #only do something with G1 commands
                       #print(e) #should never be None?
                       x = getValue(line, "X", x)
                       y = getValue(line, "Y", y)
                       z = getValue(line, "Z", z) 
                       v = getValue(line, "F", None)
                       
#                       we don't want to cause division by zero
                       if x == 0:
#                           print("binnen")
                           x = 0.0001
                       dfactor = (math.sin(waves*math.atan2(y-centerY,x-centerX))+1)*0.5;
                       newZ = (z * zScale) + (dfactor*difference*z/maxZ) - dfactor * layerheight - layerheight
# not sure why i use 0.3 here
                       newE = e + dfactor*0.3*z/maxZ
                       newE = round(newE, 3)

                       print(newZ)
#                       print(dfactor)
#                       print(difference)

                       f.write("G1 ")
                       f.write("X%0.4f " %(x))
                       f.write("Y%0.4f " %(y))
                       f.write("Z%0.4f " %(newZ))
                       f.write("E%0.4f " %(newE))
                       if v: f.write("F%0.1f " %(v))
                       f.write("\n")
                       
               else: f.write(line)
               
