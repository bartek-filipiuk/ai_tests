(function ($, Drupal) {
  Drupal.behaviors.receiptUpload = {
    attach: function (context, settings) {
      const preview = document.getElementById("receipt-preview");
      const fileInput = document.getElementById("receipt-file");
      const submitButton = document.getElementById("receipt-submit");
      const messageDiv = document.getElementById("receipt-message");
      
      // Webhook URL from make.com (to be replaced with actual URL)
      const WEBHOOK_URL = "https://hook.eu2.make.com/xkuyptsienqf7t981xzdwe1q5ra4a51s";
      
      fileInput.addEventListener("change", function(e) {
        const file = e.target.files[0];
        if (file) {
          // Show preview
          const reader = new FileReader();
          reader.onload = function(e) {
            preview.innerHTML = `<img src="${e.target.result}" alt="Preview">`;
            submitButton.disabled = false;
          };
          reader.readAsDataURL(file);
        }
      });
      
      submitButton.addEventListener("click", async function() {
        const file = fileInput.files[0];
        if (!file) return;
        
        messageDiv.textContent = "Przesyłanie...";
        submitButton.disabled = true;
        
        try {
          // First upload to make.com
          const formData = new FormData();
          formData.append("receipt", file);
          
          const response = await fetch(WEBHOOK_URL, {
            method: "POST",
            body: formData
          });
          
          if (!response.ok) throw new Error("Błąd podczas przesyłania");
          
          messageDiv.textContent = "Przetwarzanie...";
          
          // Clear the form
          preview.innerHTML = "";
          fileInput.value = "";
          submitButton.disabled = true;
          
          messageDiv.textContent = "Paragon został przesłany do analizy!";
        } catch (error) {
          messageDiv.textContent = "Wystąpił błąd: " + error.message;
          submitButton.disabled = false;
        }
      });
    }
  };
})(jQuery, Drupal);
