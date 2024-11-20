<script setup>
import VueBarcode from "@chenfengyuan/vue-barcode";
import { ref } from "vue";

const dialogVisible = ref(false);
const barcodeValue = ref("");
const partName = ref("");
const printing = ref(false);
const printArea = ref(null);
const barcodeRef = ref(null);

const barcodeOptions = {
  format: "CODE39",
  width: 2,
  height: 100,
  displayValue: false,
  fontOptions: "bold",
  font: "monospace",
  textAlign: "center",
  textPosition: "bottom",
  textMargin: 2,
  fontSize: 20,
  background: "#ffffff",
  lineColor: "#000000",
};

const printBarcode = () => {
  const printWindow = window.open("", "_blank");

  // Wait for barcode to be rendered
  setTimeout(() => {
    const barcodeImage = barcodeRef.value.$el;
    const printContent = `
      <html>
        <head>
          <title>Print Barcode</title>
          <style>
            @media print {
              body {
                margin: 0;
                padding: 20px;
              }
              .print-area {
                display: flex;
                flex-direction: column;
                align-items: center;
                gap: 10px;
              }
              .part-name {
                font-size: 18px;
                font-weight: bold;
              }
              .barcode-value {
                font-size: 16px;
                margin-top: 5px;
              }
              img {
                max-width: 100%;
                height: auto;
              }
            }
          </style>
        </head>
        <body>
          <div class="print-area">
            <div class="part-name">${partName.value}</div>
            <img src="${barcodeImage.toDataURL()}" alt="Barcode">
            <div class="barcode-value">${barcodeValue.value}</div>
          </div>
          <script>
            window.onload = () => {
              window.print();
              window.onafterprint = () => window.close();
            }
          <\/script>
        </body>
      </html>
    `;

    printWindow.document.write(printContent);
    printWindow.document.close();
  }, 100);
};

const printSato = async () => {
  if (!navigator.serial) {
    alert("Web Serial API not supported");
    return;
  }

  printing.value = true;
  try {
    const port = await navigator.serial.requestPort();
    await port.open({ baudRate: 9600 });

    const writer = port.writable.getWriter();
    const data = buildSatoCommand();
    const encoder = new TextEncoder();
    await writer.write(encoder.encode(data));

    writer.releaseLock();
    await port.close();
    dialogVisible.value = false;
  } catch (error) {
    console.error("Print failed:", error);
    alert("Printing failed: " + error.message);
  } finally {
    printing.value = false;
  }
};

const buildSatoCommand = () => {
  const STX = String.fromCharCode(0x02);
  const ESC = String.fromCharCode(0x1b);
  const ETX = String.fromCharCode(0x03);
  let EditWk = "";

  EditWk = STX;
  EditWk += ESC + "A";
  EditWk += ESC + "A1V00200H0600";
  EditWk +=
    ESC + "%0" + ESC + "V0030" + ESC + "H0220" + ESC + "P00" + ESC + "L0101";
  EditWk += ESC + "XM " + partName.value;
  EditWk += ESC + "V0060" + ESC + "H0260";
  EditWk += ESC + "D101060*" + barcodeValue.value + "*";
  EditWk +=
    ESC + "%0" + ESC + "V00130" + ESC + "H0230" + ESC + "P00" + ESC + "L0101";
  EditWk += ESC + "X22,*" + barcodeValue.value + "*";
  EditWk += ESC + "Q1";
  EditWk += ESC + "Z";
  EditWk += ETX;

  return EditWk;
};

const openDialog = (partcode, partname) => {
  barcodeValue.value = partcode;
  partName.value = partname;
  dialogVisible.value = true;
};

defineExpose({ openDialog });
</script>

<template>
  <VDialog v-model="dialogVisible" width="500">
    <DialogCloseBtn @click="dialogVisible = false" />
    <VCard class="share-project-dialog pa-2 pa-sm-4">
      <VCardTitle class="text-h4 text-center pa-4">Barcode Label</VCardTitle>
      <VCardText>
        <div ref="printArea" class="d-flex flex-column align-center pa-4">
          <div class="text-h6 mb-4">{{ partName }}</div>
          <VueBarcode
            ref="barcodeRef"
            :value="barcodeValue"
            :options="barcodeOptions"
            class="mb-4"
          />
          <div class="text-subtitle-1">{{ barcodeValue }}</div>
        </div>
      </VCardText>
      <VCardActions class="justify-center">
        <VBtn
          color="primary"
          @click="printSato"
          :loading="printing"
          prepend-icon="tabler-printer"
        >
          SATO Print
        </VBtn>
        <VBtn
          color="secondary"
          @click="printBarcode"
          prepend-icon="tabler-printer"
        >
          Normal Print
        </VBtn>
      </VCardActions>
    </VCard>
  </VDialog>
</template>
