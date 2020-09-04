import yaml
import asyncio
import magic
import aiofiles.os
import os
import sys
import tempfile
import qrcode
from qrcode.image.pure import PymagingImage
from PIL import Image
from nio import AsyncClient, LoginResponse, UploadResponse

with open("config.yaml", "rb") as fp:
    config = yaml.safe_load(fp.read())


async def send_image(client, room_id, qrmsg):
    mime_type = "image/png"

    qr = qrcode.QRCode(
        version=1,
        error_correction=qrcode.constants.ERROR_CORRECT_L,
        box_size=10,
        border=4,
    )
    qr.add_data(qrmsg)
    qr.make(fit=True)
    im = qr.make_image(fill_color="black", back_color="white", image_factory=qrcode.image.pure.PymagingImage)
    the_image = tempfile.NamedTemporaryFile(prefix="datemp", suffix=".png", delete=False)
    image = the_image.name
    im.save(the_image)
    the_image.close()
    nim = Image.open(image)  
    (width, height) = nim.size  # im.size returns (width,height) tuple

    file_stat = await aiofiles.os.stat(image)
    async with aiofiles.open(image, "r+b") as f:
     resp, maybe_keys = await client.upload(
         f,
         content_type=mime_type,  # image/jpeg
         filename=os.path.basename(image),
            filesize=file_stat.st_size)
    if (isinstance(resp, UploadResponse)):
        print("Image was uploaded successfully to server. ")
    else:
        print(f"Failed to upload image. Failure response: {resp}")

    content = {
        "body": os.path.basename(image),  # descriptive title
        "info": {
            "size": file_stat.st_size,
            "mimetype": mime_type,
            "thumbnail_info": None,  # TODO
            "w": width,  # width in pixel
            "h": height,  # height in pixel
            "thumbnail_url": None,  # TODO
        },
        "msgtype": "m.image",
        "url": resp.content_uri,
    }

    try:
        await client.room_send(
            room_id,
            message_type="m.room.message",
            content=content
        )
        print("Image was sent successfully")
    except Exception:
        print(f"Image send of file {image} failed.")


async def main():
    client = AsyncClient(config["matrix"]["homeserver_url"],config["matrix"]["matrix_id"])

    await client.login(config["matrix"]["password"])
    await client.room_send(
        room_id=config["matrix"]["room_id"],
        message_type="m.room.message",
        content={
            "msgtype": "m.text",
            "body": "Dummy Transaction!"
        }
    )
    qrmsg = "This is a dummy transaction to sign"
    room_id=config["matrix"]["room_id"]
    await send_image(client, room_id, qrmsg)
    await client.close()

asyncio.get_event_loop().run_until_complete(main())

