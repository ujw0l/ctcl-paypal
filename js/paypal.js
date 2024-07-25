

document.addEventListener('DOMContentLoaded',()=>{

    let submitButton = document.querySelector('.ctcl-checkout-button'); 
    

    Array.from(document.querySelectorAll('input[name=payment_option]')).map(x=>{

     x.addEventListener('change',(e)=>{

        if(e.target.value == 'ctcl_paypal' &&  e.target.checked == true){

            submitButton.style.display = 'none';

            let sucessCont = document.querySelector('#paypal-payment-sucess');

            if(sucessCont != null){
                submitButton.style.display = ''; 
            }


        }else{

            submitButton.style.display = '';

        }
        
     })   

    })
    
        
  

    paypal.Buttons({
        createOrder: function(data, actions) {
            return actions.order.create({
                purchase_units: [{
                    amount: {
                        value: document.querySelector('#ctcl-subtotal-hidden-input').value  // Set the amount to be charged
                    }
                }]
            });
        },
        onApprove: function(data, actions) {
            return actions.order.capture().then(function(details) {


                document.querySelector('#paypal-button-container').innerHTML = '<p  id="paypal-payment-sucess"  >Paypal Payment Sucessful.</p>';

                submitButton.click();
                submitButton.style.display = '';
            });



        },
        onError: function(err) {
            console.error(err);
            document.querySelector('#ctcl-paypal-error').style.dispay = 'block'; 
        }
    }).render('#paypal-button-container');





})