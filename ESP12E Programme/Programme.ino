#include <LiquidCrystal_I2C.h>
#include <Wire.h>
#include <ESP8266WiFi.h>
#include <ESP8266HTTPClient.h>
#include <SPI.h>
#include <MFRC522.h>

#define BUZZER_PIN D0
#define TOGGLE_PIN D8
#define I2C_SDA D3
#define I2C_SCL D4
#define RST_PIN D1
#define SS_PIN  D2

LiquidCrystal_I2C lcd (0x27, 16, 2);
MFRC522 rfid(SS_PIN, RST_PIN);

String CardID = "";
String ScanMsg = "Ready To Scan...";
String RegisterMsg = "Scan To Register";
String ChangeMode = "Mode:";
String Msg = "";

bool Mode = false;



const char* ssid = "Dialog 4G";
const char* password = "123456789A";



void setup() {

  pinMode(BUZZER_PIN, OUTPUT);
  digitalWrite(BUZZER_PIN, LOW);

  pinMode(TOGGLE_PIN, INPUT);

  Serial.begin(115200);
  SPI.begin();
  Wire.begin(I2C_SDA, I2C_SCL);

  rfid.PCD_Init();
  lcd.init();

  lcd.backlight();


  wifiConnect();

  lcd.setCursor(0, 0);
  lcd.print(ScanMsg);



}

void loop() {

  
  // if(!Mode){
  //   lcd.clear();
  //   lcd.setCursor(0, 0);
  //   lcd.print(ScanMsg);
  // }

  if (digitalRead(TOGGLE_PIN)){
    digitalWrite(BUZZER_PIN, HIGH);
    delay(50);
    digitalWrite(BUZZER_PIN, LOW);

    if(Mode){
        lcd.clear();
        lcd.setCursor(0, 0);
        lcd.print(ChangeMode + " Scan");
        
        delay(2000);

        Msg = ScanMsg;

        lcd.clear();
        lcd.setCursor(0, 0);
        lcd.print(Msg);

        Mode = false;
    }else{

        lcd.clear();
        lcd.setCursor(0, 0);
        lcd.print(ChangeMode + " Register");
        
        delay(2000);

        Msg = RegisterMsg;

        lcd.clear();
        lcd.setCursor(0, 0);
        lcd.print(Msg);

        Mode = true;
    }

  }

  if(rfid.PICC_IsNewCardPresent()){

     if(rfid.PICC_ReadCardSerial()){

    digitalWrite(BUZZER_PIN, HIGH);
    delay(300);
    digitalWrite(BUZZER_PIN, LOW);

    CardID.clear();

    for (byte i=0; i < rfid.uid.size; i++){
      if (rfid.uid.uidByte[i] < 0x10){
        CardID += "0";
      }

      CardID += String(rfid.uid.uidByte[i], HEX);

    }

    CardID.toUpperCase();

    postRequest(CardID, Mode);

    lcd.clear();
    lcd.setCursor(0, 0);
    lcd.print("UID: " + CardID);

    rfid.PICC_HaltA();
    rfid.PCD_StopCrypto1();

    delay(1000);

    lcd.clear();
    lcd.setCursor(0, 0);
    
    if(!Mode){
      lcd.print(ScanMsg);
    }else{
      lcd.print(RegisterMsg);
    }

    }else{
      return;
    }
  }else{
    return;
  }
 
}


void wifiConnect() {

  WiFi.begin(ssid,password);

  while(WiFi.status() != WL_CONNECTED){
    Serial.println("Trying to Connect to Wifi.....");
    delay(1000);
  }

  if (WiFi.status() == WL_CONNECTED){
    Serial.println("Wifi Connected");
  }

}

void postRequest(String CardID, bool mode){

  if (WiFi.status() == WL_CONNECTED){

    HTTPClient http;
    WiFiClient client;

    http.begin(client,"http://192.168.8.159:8080/api/rfid/scan.php");
    http.addHeader("Content-Type", "application/json");

    String registerJson = "{"
            "\"mode\":\"register\""","
            "\"tag_id\":\"" +CardID+ "\""
            "}";
    
    String scanJson = "{"
            "\"mode\":\"scan\""","
            "\"tag_id\":\"" +CardID+ "\""
            "}";

    if (Mode){
        
        http.POST(registerJson);

      }else{

        http.POST(scanJson);

      }
    
    
    // Serial.print("Response Code: ");
    // Serial.println(responseCode);

    // Serial.print("Payload Sent: ");
    // Serial.println(scanJson);

    http.end();

  }

}
