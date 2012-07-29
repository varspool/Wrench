-----------------
Wrench\\Util\\Ssl
-----------------

.. php:namespace: Wrench\\Util

.. php:class:: Ssl

    .. php:method:: generatePemFile(string $pem_file, string $pem_passphrase, string $country_name, string $state_or_province_name, string $locality_name, $organization_name, $organizational_unit_name, $common_name, string $email_address)

        Generates a new PEM File given the informations

        :param string $pem_file:                 the path of the PEM file to create
        :param string $pem_passphrase:           the passphrase to protect the PEM file or if you don't want to use a passphrase
        :param string $country_name:             the country code of the new PEM file. e.g.: EN
        :param string $state_or_province_name:   the state or province name of the new PEM file
        :param string $locality_name:            the name of the locality
        :param unknown $organization_name:
        :param unknown $organizational_unit_name:
        :param unknown $common_name:
        :param string $email_address:            the email address
