window.addEventListener("load", function () {
  // Add the following JavaScript code to store the form field values in local storage
  if (isAdvania) {
    var form = document.querySelector(".cart");

    form.addEventListener("submit", function (event) {
      event.preventDefault();

      // Get the form data
      const formData = new FormData(form);

      // Convert the form data to an object
      const formValues = {};
      for (const [key, value] of formData.entries()) {
        const input = form.querySelector(`[name="${key}"]`);
        // Skip over excluded fields and input fields of type hidden
        if (
          !fieldsToExclude.includes(key) &&
          input &&
          input.type !== "hidden"
        ) {
          formValues[key] = value;
        }
      }

      // Store the form data in local storage
      localStorage.setItem("form_field_values", JSON.stringify(formValues));

      // Submit the form
      form.submit();
    });
  }

  // Add the following JavaScript code to populate the form fields with the values from local storage
  const formFieldValues = localStorage.getItem("form_field_values");
  if (formFieldValues) {
    const formValues = JSON.parse(formFieldValues);
    const form = document.querySelector(".cart");
    const formInputs = form.querySelectorAll("input, select, textarea");

    formInputs.forEach(function (input) {
      const inputName = input.getAttribute("name");

      if (formValues[inputName]) {
        if (input.type == "radio" && input.value == formValues[inputName]) {
          input.click();
          input.value = formValues[inputName];
        } else {
          input.value = formValues[inputName];
        }
      }
    });
  }

  // Add the following JavaScript code to clear the form field values from local storage when user clicks the place order button in the checkout
  const formCheckout = document.querySelector(".checkout.woocommerce-checkout");
  if (formCheckout) {
    formCheckout.addEventListener("submit", function () {
      localStorage.removeItem("form_field_values");
    });
  }
});
