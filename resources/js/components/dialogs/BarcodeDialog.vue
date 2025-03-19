<script setup>
import VueBarcode from "@chenfengyuan/vue-barcode";
import { onMounted, ref } from "vue";
import { useToast } from "vue-toastification";
import { VCardText } from "vuetify/lib/components/index.mjs";

const toast = useToast();

const dialogVisible = ref(false);
const barcodeValue = ref("");
const partName = ref("");
const printing = ref(false);
const printArea = ref(null);
const barcodeRef = ref(null);

const printers = ref([]);
const printerName = ref(null);

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
  printing.value = true;

  if (printerName.value === null) {
    console.error("Select printer first");
  }

  try {
    // Send the simplified request to the new endpoint
    const response = await fetch("http://localhost:8085/print-sato", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({
        PrinterName: printerName.value,
        Title: partName.value,
        Barcode: barcodeValue.value,
      }),
    });

    const result = await response.json();

    if (!result.Success) {
      throw new Error(result.Message);
    }

    toast.success("Print job sent successfully");
  } catch (error) {
    console.error("Print failed:", error);
    toast.error("Printing failed: " + error.message);
  } finally {
    printing.value = false;
  }
};

const fetchPrinters = async () => {
  try {
    const response = await fetch("http://localhost:8085/printers");
    printers.value = await response.json();

    // Set default printer if one exists
    if (printers.value.length > 0 && !printerName.value) {
      printerName.value = printers.value[0];
    }
  } catch (error) {
    console.error("Failed to fetch printers:", error);
    printers.value = [];
  }
};

const openDialog = (partcode, partname) => {
  barcodeValue.value = partcode;
  partName.value = partname;
  dialogVisible.value = true;
};

defineExpose({ openDialog });

onMounted(() => {
  fetchPrinters();
});
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

      <VCardText class="justify-center">
        <AppSelect
          v-model="printerName"
          :items="printers"
          label="Printer"
          outlined
          dense
          class="mb-4"
        />
        <div class="text-subtitle-1">
          Couldn't connect to the client printer helper
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
