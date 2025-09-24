import json
import re
import os
from typing import Dict, List

class ProductManager:
    def __init__(self, js_file_path: str = "assets/js/catalogo_produtos.js"):
        self.js_file_path = js_file_path
        self.products = []
        self.load_existing_products()
    
    def load_existing_products(self):
        """Load existing products from the JavaScript file"""
        try:
            if os.path.exists(self.js_file_path):
                with open(self.js_file_path, 'r', encoding='utf-8') as file:
                    content = file.read()
                
                # More robust regex to extract the products array
                pattern = r'const products = \[(.*?)\];'
                match = re.search(pattern, content, re.DOTALL)
                
                if match:
                    products_content = match.group(1).strip()
                    
                    # If there's content, parse it
                    if products_content and not products_content.startswith('//'):
                        # Split by product objects (look for closing brace followed by comma or end)
                        product_blocks = re.findall(r'\{([^}]+)\}', products_content)
                        
                        for block in product_blocks:
                            try:
                                product = {}
                                # Extract each field
                                brand_match = re.search(r'brand:\s*["\']([^"\']+)["\']', block)
                                title_match = re.search(r'title:\s*["\']([^"\']+)["\']', block)
                                sku_match = re.search(r'sku:\s*["\']([^"\']+)["\']', block)
                                price_match = re.search(r'price:\s*([0-9.]+)', block)
                                image_match = re.search(r'image:\s*["\']([^"\']+)["\']', block)
                                
                                if brand_match and title_match and sku_match and price_match:
                                    product = {
                                        "brand": brand_match.group(1),
                                        "title": title_match.group(1),
                                        "sku": sku_match.group(1),
                                        "price": float(price_match.group(1)),
                                        "image": image_match.group(1) if image_match else f"assets/img/products/battery-{brand_match.group(1)}.jpg"
                                    }
                                    self.products.append(product)
                            except Exception as e:
                                print(f"⚠️  Error parsing product block: {e}")
                                continue
                        
                        print(f"✅ Loaded {len(self.products)} existing products")
                    else:
                        print("📭 No existing products found in file")
                else:
                    print("⚠️  No products array found in file")
            else:
                print("⚠️  JavaScript file not found, will create new one")
        except Exception as e:
            print(f"❌ Error loading products: {e}")
            self.products = []
    
    def get_available_brands(self) -> List[str]:
        """Get list of available brands from the HTML dropdown"""
        brands = [
            "samsung", "apple", "xiaomi", "lg", "motorola", "huawei", 
            "asus", "lenovo", "nokia", "positivo", "multilaser", 
            "philco", "infinix"
        ]
        return brands
    
    def validate_brand(self, brand: str) -> str:
        """Validate and return the brand in lowercase"""
        available_brands = self.get_available_brands()
        brand_lower = brand.lower().strip()
        
        if brand_lower not in available_brands:
            print(f"⚠️  Brand '{brand}' not in available brands.")
            print(f"Available brands: {', '.join(available_brands)}")
            return None
        
        return brand_lower
    
    def generate_sku(self, brand: str, model: str) -> str:
        """Generate SKU based on brand and model"""
        brand_code = brand.upper()[:3]
        model_clean = re.sub(r'[^a-zA-Z0-9]', '', model.upper())[:8]
        
        # Check if SKU already exists
        base_sku = f"VTN-{brand_code}-{model_clean}"
        sku = base_sku
        counter = 1
        
        while any(product['sku'] == sku for product in self.products):
            sku = f"{base_sku}-{counter}"
            counter += 1
        
        return sku
    
    def add_product_interactive(self):
        """Interactive product addition"""
        print("\n" + "="*50)
        print("🔋 ADICIONAR NOVO PRODUTO - CATÁLOGO VTN")
        print("="*50)
        
        try:
            # Brand selection
            print(f"\n📱 Marcas disponíveis:")
            brands = self.get_available_brands()
            for i, brand in enumerate(brands, 1):
                print(f"  {i:2d}. {brand.capitalize()}")
            
            while True:
                brand_input = input(f"\n🏷️  Digite o número da marca ou nome: ").strip()
                
                # Check if it's a number
                if brand_input.isdigit():
                    brand_num = int(brand_input)
                    if 1 <= brand_num <= len(brands):
                        brand = brands[brand_num - 1]
                        break
                    else:
                        print(f"❌ Número inválido. Digite entre 1 e {len(brands)}")
                else:
                    # Check if it's a brand name
                    brand = self.validate_brand(brand_input)
                    if brand:
                        break
            
            # Product title
            title = input(f"\n📝 Título do produto (ex: Bateria {brand.capitalize()} Galaxy S20 4000mAh): ").strip()
            if not title:
                title = f"Bateria {brand.capitalize()}"
            
            # Capacity
            while True:
                try:
                    capacity = input("🔋 Capacidade em mAh (ex: 4000): ").strip()
                    if capacity and capacity.isdigit():
                        capacity_int = int(capacity)
                        break
                    else:
                        print("❌ Digite apenas números para a capacidade")
                except ValueError:
                    print("❌ Capacidade inválida")
            
            # Price
            while True:
                try:
                    price_input = input("💰 Preço (ex: 149.90): R$ ").strip().replace(',', '.')
                    price = float(price_input)
                    break
                except ValueError:
                    print("❌ Preço inválido. Use formato: 149.90")
            
            # Model for SKU generation
            model = input(f"📱 Modelo do dispositivo (para SKU): ").strip()
            if not model:
                model = "GENERIC"
            
            # Generate SKU
            sku = self.generate_sku(brand, model)
            print(f"🏷️  SKU gerado: {sku}")
            
            # Image path
            image_default = f"assets/img/products/battery-{brand}-{model.lower().replace(' ', '-')}.jpg"
            image = input(f"🖼️  Caminho da imagem (Enter para padrão): ").strip()
            if not image:
                image = image_default
            
            # Create product object
            new_product = {
                "brand": brand,
                "title": title,
                "sku": sku,
                "price": price,
                "image": image
            }
            
            # Show summary
            print(f"\n" + "="*50)
            print("📋 RESUMO DO PRODUTO:")
            print("="*50)
            print(f"🏷️  Marca: {brand.capitalize()}")
            print(f"📝 Título: {title}")
            print(f"🔖 SKU: {sku}")
            print(f"💰 Preço: R$ {price:.2f}")
            print(f"🖼️  Imagem: {image}")
            
            # Confirm
            confirm = input(f"\n✅ Confirmar adição? (s/N): ").strip().lower()
            if confirm in ['s', 'sim', 'y', 'yes']:
                self.products.append(new_product)
                self.save_products()
                print(f"✅ Produto adicionado com sucesso!")
                return True
            else:
                print("❌ Produto não adicionado.")
                return False
                
        except KeyboardInterrupt:
            print(f"\n\n❌ Operação cancelada pelo usuário.")
            return False
        except Exception as e:
            print(f"❌ Erro ao adicionar produto: {e}")
            return False
    
    def save_products(self):
        """Save products back to JavaScript file"""
        try:
            # Read the original file to preserve the rest of the content
            original_content = ""
            if os.path.exists(self.js_file_path):
                with open(self.js_file_path, 'r', encoding='utf-8') as file:
                    original_content = file.read()
            
            # Convert products to JavaScript format
            js_products = "const products = [\n"
            
            for i, product in enumerate(self.products):
                js_products += "    {\n"
                js_products += f'        brand: "{product["brand"]}",\n'
                js_products += f'        title: "{product["title"]}",\n'
                js_products += f'        sku: "{product["sku"]}",\n'
                js_products += f'        price: {product["price"]},\n'
                js_products += f'        image: "{product["image"]}"\n'
                js_products += "    }"
                
                if i < len(self.products) - 1:
                    js_products += ","
                js_products += "\n"
            
            js_products += "    // Adicione mais produtos conforme sua planilha\n"
            js_products += "];\n"
            
            # Find the rest of the file content (everything after the products array)
            rest_of_file = ""
            if original_content:
                # Find where the products array ends
                pattern = r'const products = \[.*?\];'
                match = re.search(pattern, original_content, re.DOTALL)
                if match:
                    # Get everything after the products array
                    rest_of_file = original_content[match.end():]
                else:
                    # If no products array found, find the first function or comment
                    lines = original_content.split('\n')
                    start_index = 0
                    for i, line in enumerate(lines):
                        if line.strip().startswith('//') and 'função' in line.lower():
                            start_index = i
                            break
                        elif line.strip().startswith('function') or line.strip().startswith('document.'):
                            start_index = i
                            break
                    
                    if start_index > 0:
                        rest_of_file = '\n' + '\n'.join(lines[start_index:])
            
            # Write the complete file
            with open(self.js_file_path, 'w', encoding='utf-8') as file:
                file.write(js_products)
                if rest_of_file.strip():
                    file.write(rest_of_file)
            
            print(f"💾 Arquivo salvo: {self.js_file_path}")
            
        except Exception as e:
            print(f"❌ Erro ao salvar arquivo: {e}")
    
    def list_products(self):
        """List all existing products"""
        if not self.products:
            print("📭 Nenhum produto encontrado.")
            return
        
        print(f"\n📋 PRODUTOS CADASTRADOS ({len(self.products)} total):")
        print("="*80)
        
        for i, product in enumerate(self.products, 1):
            print(f"{i:2d}. {product['brand'].upper()} | {product['title']}")
            print(f"    SKU: {product['sku']} | Preço: R$ {product['price']:.2f}")
            print(f"    Imagem: {product['image']}")
            print("-" * 80)
    
    def bulk_add_from_input(self):
        """Add multiple products in sequence"""
        print("\n🔄 MODO ADIÇÃO EM LOTE")
        print("Digite 'sair' a qualquer momento para parar\n")
        
        added_count = 0
        while True:
            print(f"\n--- Produto #{added_count + 1} ---")
            if self.add_product_interactive():
                added_count += 1
                
            continue_adding = input(f"\n➕ Adicionar outro produto? (S/n): ").strip().lower()
            if continue_adding in ['n', 'no', 'não', 'nao']:
                break
        
        print(f"\n✅ Processo concluído! {added_count} produto(s) adicionado(s).")

def main():
    """Main function"""
    print("🔋 GERENCIADOR DE PRODUTOS - GRUPO VTN")
    print("="*50)
    
    manager = ProductManager()
    
    while True:
        print(f"\n📋 MENU PRINCIPAL:")
        print("1. ➕ Adicionar produto")
        print("2. 📋 Listar produtos")
        print("3. 🔄 Adição em lote")
        print("4. 🚪 Sair")
        
        choice = input(f"\n🎯 Escolha uma opção (1-4): ").strip()
        
        if choice == '1':
            manager.add_product_interactive()
        elif choice == '2':
            manager.list_products()
        elif choice == '3':
            manager.bulk_add_from_input()
        elif choice == '4':
            print("👋 Até logo!")
            break
        else:
            print("❌ Opção inválida. Tente novamente.")

if __name__ == "__main__":
    main()
